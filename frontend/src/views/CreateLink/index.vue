<template>
  <div class="create-link-page">
    <a-card title="创建一次性链接">
      <a-form
        :model="formState"
        :rules="rules"
        :label-col="{ span: 6 }"
        :wrapper-col="{ span: 14 }"
        @finish="handleSubmit"
      >
        <a-form-item label="业务类型" name="content_type">
          <a-select v-model:value="formState.content_type" placeholder="请选择业务类型">
            <a-select-option value="secret_share">密文分享</a-select-option>
            <a-select-option value="email_verify">邮箱验证</a-select-option>
            <a-select-option value="password_reset">密码重置</a-select-option>
            <a-select-option value="magic_login">魔法登录</a-select-option>
            <a-select-option value="file_download">文件下载</a-select-option>
            <a-select-option value="qrcode_auth">二维码验证</a-select-option>
            <a-select-option value="invite_code">邀请码</a-select-option>
            <a-select-option value="custom">自定义</a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="业务数据" name="content_data">
          <a-input
            v-model:value="formState.content_data"
            type="textarea"
            :rows="4"
            placeholder="请输入业务数据，支持文本或JSON格式"
          />
        </a-form-item>

        <a-form-item label="最大访问次数" name="max_visits">
          <a-input-number v-model:value="formState.max_visits" :min="1" :max="100" style="width: 200px" />
          <span class="form-hint">默认1次，链接被访问达到此次数后失效</span>
        </a-form-item>

        <a-form-item label="过期时间" name="expire_time">
          <a-radio-group v-model:value="expireType">
            <a-radio value="never">永不过期</a-radio>
            <a-radio value="preset">预设时间</a-radio>
            <a-radio value="custom">自定义</a-radio>
          </a-radio-group>
          <a-form-item-rest>
            <div v-if="expireType === 'preset'" style="margin-top: 8px">
              <a-select v-model:value="presetExpire" style="width: 200px">
                <a-select-option :value="900">15分钟</a-select-option>
                <a-select-option :value="3600">1小时</a-select-option>
                <a-select-option :value="86400">24小时</a-select-option>
                <a-select-option :value="604800">7天</a-select-option>
                <a-select-option :value="2592000">30天</a-select-option>
              </a-select>
            </div>
            <div v-if="expireType === 'custom'" style="margin-top: 8px">
              <a-date-picker
                v-model:value="customExpireDate"
                show-time
                format="YYYY-MM-DD HH:mm:ss"
                placeholder="选择过期时间"
              />
            </div>
          </a-form-item-rest>
        </a-form-item>

        <a-form-item label="高级选项">
          <a-space direction="vertical">
            <a-checkbox v-model:checked="formState.bind_ip">绑定IP地址（首次访问的IP）</a-checkbox>
            <a-checkbox v-model:checked="formState.notify_on_visit">访问时发送通知</a-checkbox>
          </a-space>
        </a-form-item>

        <a-form-item :wrapper-col="{ offset: 6, span: 14 }">
          <a-space>
            <a-button type="primary" html-type="submit" :loading="loading">创建链接</a-button>
            <a-button @click="handleReset">重置</a-button>
          </a-space>
        </a-form-item>
      </a-form>
    </a-card>

    <!-- 成功弹窗 -->
    <a-modal
      v-model:open="successModalVisible"
      title="链接创建成功"
      :footer="null"
      width="500px"
    >
      <a-result status="success" title="一次性链接已生成">
        <template #extra>
          <div class="success-content">
            <a-input-group compact>
              <a-input :value="createdLinkUrl" readonly style="width: calc(100% - 80px)" />
              <a-button type="primary" @click="handleCopyLink">复制链接</a-button>
            </a-input-group>
            <div class="qrcode-wrapper">
              <qrcode-vue :value="createdLinkUrl" :size="200" level="M" />
            </div>
            <a-space>
              <a-button @click="handleCreateAnother">继续创建</a-button>
              <a-button type="primary" @click="router.push('/manage')">查看管理</a-button>
            </a-space>
          </div>
        </template>
      </a-result>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import dayjs, { type Dayjs } from 'dayjs'
import QrcodeVue from 'qrcode.vue'
import { useOnetimeStore } from '@/store/modules/onetime'
import type { CreateLinkParams, ContentType } from '@/api/types'

const router = useRouter()
const route = useRoute()
const store = useOnetimeStore()

const loading = ref(false)
const successModalVisible = ref(false)
const createdLinkUrl = ref('')

const formState = reactive<CreateLinkParams>({
  content_type: 'secret_share' as ContentType,
  content_data: '',
  max_visits: 1,
  expire_time: 0,
  bind_ip: false,
  notify_on_visit: false,
})

const expireType = ref<'never' | 'preset' | 'custom'>('preset')
const presetExpire = ref(86400)
const customExpireDate = ref<Dayjs | null>(null)

const rules = {
  content_type: [{ required: true, message: '请选择业务类型' }],
  content_data: [{ required: true, message: '请输入业务数据' }],
}

watch(
  [expireType, presetExpire, customExpireDate],
  () => {
    if (expireType.value === 'never') {
      formState.expire_time = 0
    } else if (expireType.value === 'preset') {
      formState.expire_time = Math.floor(Date.now() / 1000) + presetExpire.value
    } else if (expireType.value === 'custom' && customExpireDate.value) {
      formState.expire_time = customExpireDate.value.unix()
    }
  },
  { immediate: true }
)

const handleSubmit = async () => {
  loading.value = true
  try {
    const link = await store.createLink(formState)
    // [OTL] 始终使用当前域名生成链接
    createdLinkUrl.value = `${window.location.origin}/verify/${link.token}`
    successModalVisible.value = true
    message.success('链接创建成功')
  } catch (error) {
    console.error('[OTL] Create link error:', error)
  } finally {
    loading.value = false
  }
}

const handleReset = () => {
  formState.content_type = 'secret_share'
  formState.content_data = ''
  formState.max_visits = 1
  formState.bind_ip = false
  formState.notify_on_visit = false
  expireType.value = 'preset'
  presetExpire.value = 86400
  customExpireDate.value = null
}

const handleCopyLink = async () => {
  try {
    await navigator.clipboard.writeText(createdLinkUrl.value)
    message.success('链接已复制到剪贴板')
  } catch {
    message.error('复制失败，请手动复制')
  }
}

const handleCreateAnother = () => {
  successModalVisible.value = false
  handleReset()
}

onMounted(() => {
  const type = route.query.type as string
  if (type) {
    formState.content_type = type as ContentType
  }
})
</script>

<style scoped>
.create-link-page {
  max-width: 800px;
  margin: 0 auto;
}

.form-hint {
  margin-left: 8px;
  color: #999;
  font-size: 12px;
}

.success-content {
  text-align: center;
}

.qrcode-wrapper {
  margin: 24px 0;
}
</style>
