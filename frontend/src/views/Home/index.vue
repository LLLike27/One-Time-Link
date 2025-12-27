<template>
  <div class="home-page">
    <a-row :gutter="[16, 16]">
      <!-- 统计卡片 -->
      <a-col :xs="24" :sm="12" :md="6">
        <a-card>
          <a-statistic title="总链接数" :value="statistics?.total_links || 0">
            <template #prefix>
              <LinkOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :xs="24" :sm="12" :md="6">
        <a-card>
          <a-statistic title="有效链接" :value="statistics?.active_links || 0" :value-style="{ color: '#52c41a' }">
            <template #prefix>
              <CheckCircleOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :xs="24" :sm="12" :md="6">
        <a-card>
          <a-statistic title="总访问量" :value="statistics?.total_visits || 0">
            <template #prefix>
              <EyeOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :xs="24" :sm="12" :md="6">
        <a-card>
          <a-statistic title="访问成功率" :value="statistics?.success_rate || 0" suffix="%">
            <template #prefix>
              <PercentageOutlined />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
    </a-row>

    <!-- 快速创建 -->
    <a-card title="快速创建" class="section-card">
      <a-row :gutter="16">
        <a-col v-for="item in quickCreateItems" :key="item.type" :xs="12" :sm="8" :md="6">
          <a-card hoverable class="quick-card" @click="handleQuickCreate(item.type)">
            <component :is="item.icon" class="quick-icon" />
            <div class="quick-title">{{ item.title }}</div>
            <div class="quick-desc">{{ item.desc }}</div>
          </a-card>
        </a-col>
      </a-row>
    </a-card>

    <!-- 最近链接 -->
    <a-card title="最近创建" class="section-card" :loading="loading">
      <template #extra>
        <a-button type="link" @click="router.push('/manage')">查看全部</a-button>
      </template>
      <a-empty v-if="links.length === 0" description="暂无链接" />
      <a-list v-else :data-source="links" :grid="{ gutter: 16, xs: 1, sm: 2, md: 3, lg: 3, xl: 4 }">
        <template #renderItem="{ item }">
          <a-list-item>
            <a-card size="small" hoverable @click="router.push(`/detail/${item.id}`)">
              <template #title>
                <a-tag :color="formatStatus(item.status).color">{{ formatStatus(item.status).text }}</a-tag>
                {{ formatContentType(item.content_type) }}
              </template>
              <div class="link-info">
                <div>访问: {{ item.current_visits }} / {{ item.max_visits }}</div>
                <div>创建: {{ formatRelativeTime(item.created_at) }}</div>
              </div>
            </a-card>
          </a-list-item>
        </template>
      </a-list>
    </a-card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, h } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import {
  LinkOutlined,
  CheckCircleOutlined,
  EyeOutlined,
  PercentageOutlined,
  LockOutlined,
  MailOutlined,
  KeyOutlined,
  FileOutlined,
} from '@ant-design/icons-vue'
import { useOnetimeStore } from '@/store/modules/onetime'
import { formatStatus, formatContentType, formatRelativeTime } from '@/utils/format'

const router = useRouter()
const store = useOnetimeStore()
const { links, statistics, loading } = storeToRefs(store)

const quickCreateItems = [
  { type: 'secret_share', title: '密文分享', desc: '安全分享敏感信息', icon: h(LockOutlined) },
  { type: 'email_verify', title: '邮箱验证', desc: '验证邮箱地址', icon: h(MailOutlined) },
  { type: 'password_reset', title: '密码重置', desc: '重置用户密码', icon: h(KeyOutlined) },
  { type: 'file_download', title: '文件下载', desc: '临时文件授权', icon: h(FileOutlined) },
]

const handleQuickCreate = (type: string) => {
  router.push({ path: '/create', query: { type } })
}

onMounted(() => {
  store.fetchLinks({ page_size: 8 })
  store.fetchStatistics()
})
</script>

<style scoped>
.section-card {
  margin-top: 16px;
}

.quick-card {
  text-align: center;
  cursor: pointer;
}

.quick-icon {
  font-size: 32px;
  color: #1890ff;
  margin-bottom: 8px;
}

.quick-title {
  font-weight: 500;
  margin-bottom: 4px;
}

.quick-desc {
  font-size: 12px;
  color: #999;
}

.link-info {
  font-size: 12px;
  color: #666;
}
</style>
