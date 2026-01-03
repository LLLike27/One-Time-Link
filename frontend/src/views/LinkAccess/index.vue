<template>
  <div class="link-access-page">
    <div v-if="loading" class="loading">
      <a-spin size="large" tip="验证中..." />
    </div>

    <a-result v-else-if="error" status="error" :title="errorTitle" :sub-title="errorMessage">
      <template #extra>
        <a-button type="primary" class="back-home" @click="goHome">返回首页</a-button>
      </template>
    </a-result>

    <a-result v-else-if="linkData" status="success" title="验证成功">
      <template #extra>
        <!-- 密文分享 -->
        <div v-if="linkData.content_type === 'secret_share'" class="content-box">
          <a-alert message="以下内容仅展示一次，请妥善保存" type="warning" show-icon style="margin-bottom: 16px" />
          <a-card title="密文内容">
            <div class="secret-content">
              <div class="code-block-extension-header">
                <div class="code-block-extension-headerLeft">
                  <button
                    type="button"
                    class="code-block-extension-foldBtn"
                    :title="secretCollapsed ? '展开' : '折叠'"
                    :aria-label="secretCollapsed ? '展开' : '折叠'"
                    @click="secretCollapsed = !secretCollapsed"
                  >
                    <svg
                      class="code-block-extension-foldIcon"
                      :class="{ 'is-collapsed': secretCollapsed }"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <path
                        d="M16.924 9.617A1 1 0 0 0 16 9H8a1 1 0 0 0-.707 1.707l4 4a1 1 0 0 0 1.414 0l4-4a1 1 0 0 0 .217-1.09z"
                      />
                    </svg>
                  </button>
                  <span class="code-block-extension-lang">{{ secretLang }}</span>
                </div>
                <div class="code-block-extension-headerRight">
                  <a-button type="text" size="small" class="code-block-extension-copyCodeBtn" @click="handleCopy">
                    复制内容
                  </a-button>
                </div>
              </div>

              <pre v-show="!secretCollapsed" class="secret-content-body">
                <code class="code-block-extension-codeShowNum" :lang="secretLang">
                  <span
                    v-for="(line, index) in secretLines"
                    :key="index"
                    class="code-block-extension-codeLine"
                    :data-line-num="index + 1"
                  >{{ line }}</span>
                </code>
              </pre>
            </div>
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
                  <a-button type="primary" html-type="submit" block>重置密码</a-button>
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

        <a-button style="margin-top: 16px" class="back-home" @click="goHome">返回首页</a-button>
      </template>
    </a-result>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
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

const secretCollapsed = ref(false)

const secretText = computed(() => {
  const data = linkData.value?.content_data
  if (data == null) return ''
  if (typeof data === 'string') {
    const trimmed = data.trim()
    if (!trimmed) return data
    try {
      const parsed = JSON.parse(trimmed)
      if (parsed && typeof parsed === 'object') {
        return JSON.stringify(parsed, null, 2)
      }
    } catch {
      // ignore
    }
    return data
  }
  try {
    return JSON.stringify(data, null, 2)
  } catch {
    return String(data)
  }
})

const secretLang = computed(() => {
  const data = linkData.value?.content_data
  if (data == null) return 'text'
  if (typeof data !== 'string') return 'json'
  const trimmed = data.trim()
  if (!trimmed) return 'text'
  try {
    const parsed = JSON.parse(trimmed)
    if (parsed && typeof parsed === 'object') return 'json'
  } catch {
    // ignore
  }
  return 'text'
})

const secretLines = computed(() => {
  const normalized = secretText.value.replace(/\r\n/g, '\n')
  const lines = normalized.split('\n')
  return lines.length ? lines : ['']
})

const token = route.params.token as string

const goHome = () => {
  router.push('/')
}

const handleCopy = async () => {
  try {
    await navigator.clipboard.writeText(secretText.value)
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
    secretCollapsed.value = false
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
  max-width: 720px;
  margin: 0 auto;
  padding: 24px;
  background: var(--app-surface);
  border-radius: var(--app-radius);
  border: 1px solid var(--app-border);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}

.loading {
  min-height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.content-box {
  text-align: left;
  width: 100%;
}

.secret-content {
  margin-bottom: 16px;
  border-radius: 10px;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.04);
  border: 1px solid var(--app-border);
}

.code-block-extension-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: rgb(248, 248, 248);
  border-bottom: 1px solid var(--app-border);
}

.code-block-extension-headerLeft {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.code-block-extension-headerRight {
  display: flex;
  align-items: center;
}

.code-block-extension-foldBtn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  color: rgba(0, 0, 0, 0.6);
}

.code-block-extension-foldIcon {
  width: 18px;
  height: 18px;
  transition: transform 120ms ease;
}

.code-block-extension-foldIcon.is-collapsed {
  transform: rotate(-90deg);
}

.code-block-extension-lang {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.45);
  text-transform: lowercase;
}

.code-block-extension-copyCodeBtn {
  padding: 0 6px;
  height: 24px;
  font-size: 14px;
  color: #5f6b7c;
}

.secret-content-body {
  margin: 0;
  padding: 0;
  overflow: auto;
}

.code-block-extension-codeShowNum {
  display: flex;
  flex-direction: column;
  flex: 1;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 12px;
  color: rgba(0, 0, 0, 0.82);
}

.code-block-extension-codeLine {
  display: block;
  white-space: pre;
  padding: 0 12px 0 0;
}

.code-block-extension-codeLine:before {
  content: attr(data-line-num);
  color: gray;
  user-select: none;
  background-color: rgb(248, 248, 248);
  /* border-right: 1px solid var(--app-border); */
  text-align: center;
  display: inline-block;
  width: 18px;
  padding: 0 10px;
  box-sizing: content-box;
  position: sticky;
  left: 0;
  z-index: 2;
}

.back-home {
  width: 100%;
  max-width: 320px;
}

@media (max-width: 576px) {
  .link-access-page {
    padding: 16px;
  }

  .back-home {
    max-width: 100%;
  }
}
</style>
