<template>
  <div class="link-access-page">
    <a-spin v-if="loading" size="large" tip="验证中..." />

    <a-result v-else-if="error" status="error" :title="errorTitle" :sub-title="errorMessage">
      <template #extra>
        <a-button type="primary" @click="goHome">返回首页</a-button>
      </template>
    </a-result>

    <a-result v-else-if="linkData" status="success" title="验证成功">
      <template #extra>
        <!-- 密文分享 -->
        <div v-if="linkData.content_type === 'secret_share'" class="content-box">
          <a-alert message="以下内容仅展示一次，请妥善保存" type="warning" show-icon style="margin-bottom: 16px" />
          <a-card title="密文内容">
            <pre class="secret-content">{{ linkData.content_data }}</pre>
            <a-button type="primary" @click="handleCopy">复制内容</a-button>
          </a-card>
        </div>

        <!-- 文件下载 -->
        <div v-else-if="linkData.content_type === 'file_download'" class="content-box">
          <a-card title="文件下载">
            <p>文件已准备就绪，点击下方按钮开始下载</p>
            <a-button type="primary" @click="handleDownload">下载文件</a-button>
          </a-card>
        </div>

        <!-- 邮箱验证 -->
        <div v-else-if="linkData.content_type === 'email_verify'" class="content-box">
          <a-card>
            <a-result status="success" title="邮箱验证成功" sub-title="您的邮箱已成功验证" />
          </a-card>
        </div>

        <!-- 密码重置 -->
        <div v-else-if="linkData.content_type === 'password_reset'" class="content-box">
          <a-card title="重置密码">
            <a-form :model="resetForm" @finish="handleResetPassword">
              <a-form-item name="password" :rules="[{ required: true, message: '请输入新密码' }]">
                <a-input-password v-model:value="resetForm.password" placeholder="请输入新密码" />
              </a-form-item>
              <a-form-item name="confirmPassword" :rules="[{ required: true, message: '请确认新密码' }]">
                <a-input-password v-model:value="resetForm.confirmPassword" placeholder="请确认新密码" />
              </a-form-item>
              <a-form-item>
                <a-button type="primary" html-type="submit">重置密码</a-button>
              </a-form-item>
            </a-form>
          </a-card>
        </div>

        <!-- 其他类型 -->
        <div v-else class="content-box">
          <a-card title="链接内容">
            <pre>{{ JSON.stringify(linkData.content_data, null, 2) }}</pre>
          </a-card>
        </div>

        <a-button style="margin-top: 16px" @click="goHome">返回首页</a-button>
      </template>
    </a-result>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { onetimeApi } from '@/api/onetime'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref(false)
const errorTitle = ref('')
const errorMessage = ref('')
const linkData = ref<{ content_type: string; content_data: any } | null>(null)

const resetForm = reactive({
  password: '',
  confirmPassword: '',
})

const token = route.params.token as string

const goHome = () => {
  router.push('/')
}

const handleCopy = async () => {
  try {
    await navigator.clipboard.writeText(String(linkData.value?.content_data || ''))
    message.success('已复制到剪贴板')
  } catch {
    message.error('复制失败')
  }
}

const handleDownload = () => {
  const data = linkData.value?.content_data
  if (data?.url) {
    window.open(data.url, '_blank')
  } else {
    message.error('下载链接不可用')
  }
}

const handleResetPassword = () => {
  if (resetForm.password !== resetForm.confirmPassword) {
    message.error('两次输入的密码不一致')
    return
  }
  message.success('密码重置成功')
  setTimeout(() => goHome(), 1500)
}

onMounted(async () => {
  try {
    linkData.value = await onetimeApi.accessLink(token)
  } catch (err: any) {
    error.value = true
    errorTitle.value = '访问失败'
    const msg = err.response?.data?.message || err.message || '链接无效或已过期'
    errorMessage.value = msg
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.link-access-page {
  max-width: 600px;
  margin: 0 auto;
  padding: 24px;
}

.content-box {
  text-align: left;
  width: 100%;
}

.secret-content {
  background: #f5f5f5;
  padding: 16px;
  border-radius: 4px;
  white-space: pre-wrap;
  word-break: break-all;
  margin-bottom: 16px;
}
</style>
