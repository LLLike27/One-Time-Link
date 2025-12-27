<template>
  <div class="link-detail-page">
    <a-spin :spinning="loading">
      <a-page-header
        :title="formatContentType(link?.content_type || '')"
        :sub-title="link?.token"
        @back="router.back()"
      >
        <template #extra>
          <a-space>
            <a-button @click="handleCopyLink">复制链接</a-button>
            <a-popconfirm
              v-if="link?.status === 1"
              title="确定要撤销此链接吗？"
              @confirm="handleRevoke"
            >
              <a-button danger>撤销链接</a-button>
            </a-popconfirm>
          </a-space>
        </template>

        <a-descriptions :column="{ xs: 1, sm: 2, md: 3 }">
          <a-descriptions-item label="状态">
            <a-tag :color="formatStatus(link?.status || 0).color">
              {{ formatStatus(link?.status || 0).text }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="访问次数">
            {{ link?.current_visits }} / {{ link?.max_visits }}
          </a-descriptions-item>
          <a-descriptions-item label="过期时间">
            {{ formatExpireTime(link?.expire_time || 0) }}
          </a-descriptions-item>
          <a-descriptions-item label="创建时间">
            {{ formatTime(link?.created_at || '') }}
          </a-descriptions-item>
          <a-descriptions-item label="更新时间">
            {{ formatTime(link?.updated_at || '') }}
          </a-descriptions-item>
          <a-descriptions-item label="IP绑定">
            {{ link?.bind_ip || '未绑定' }}
          </a-descriptions-item>
          <a-descriptions-item label="访问通知">
            {{ link?.notify_on_visit ? '已开启' : '未开启' }}
          </a-descriptions-item>
        </a-descriptions>
      </a-page-header>

      <!-- 访问日志 -->
      <a-card title="访问日志" style="margin-top: 16px">
        <a-table
          :columns="logColumns"
          :data-source="link?.logs || []"
          row-key="id"
          :pagination="{ pageSize: 10 }"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'visit_result'">
              <a-tag :color="formatVisitResult(record.visit_result).color">
                {{ formatVisitResult(record.visit_result).text }}
              </a-tag>
            </template>
            <template v-else-if="column.key === 'visit_time'">
              {{ formatTime(record.visit_time) }}
            </template>
            <template v-else-if="column.key === 'location'">
              {{ [record.geo_country, record.geo_region, record.geo_city].filter(Boolean).join(' ') || '-' }}
            </template>
          </template>
        </a-table>
      </a-card>
    </a-spin>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { storeToRefs } from 'pinia'
import { useOnetimeStore } from '@/store/modules/onetime'
import { formatStatus, formatContentType, formatExpireTime, formatTime, formatVisitResult } from '@/utils/format'

const router = useRouter()
const route = useRoute()
const store = useOnetimeStore()
const { currentLink: link, loading } = storeToRefs(store)

const logColumns = [
  { title: '访问时间', dataIndex: 'visit_time', key: 'visit_time', width: 180 },
  { title: 'IP地址', dataIndex: 'ip_address', key: 'ip_address', width: 140 },
  { title: '地理位置', key: 'location', width: 150 },
  { title: '访问结果', dataIndex: 'visit_result', key: 'visit_result', width: 100 },
  { title: 'User-Agent', dataIndex: 'user_agent', key: 'user_agent'},
]

const linkId = computed(() => Number(route.params.id))

const handleCopyLink = async () => {
  if (!link.value) return
  // [OTL] 始终使用当前域名生成链接
  const url = `${window.location.origin}/verify/${link.value.token}`
  try {
    await navigator.clipboard.writeText(url)
    message.success('链接已复制')
  } catch {
    message.error('复制失败')
  }
}

const handleRevoke = async () => {
  if (!link.value) return
  try {
    await store.revokeLink(link.value.id)
    message.success('链接已撤销')
  } catch {
    message.error('撤销失败')
  }
}

onMounted(() => {
  store.fetchLinkDetail(linkId.value)
})

onUnmounted(() => {
  store.clearCurrentLink()
})
</script>

<style scoped>
.link-detail-page {
  background: #fff;
  padding: 24px;
  border-radius: 8px;
}
</style>
