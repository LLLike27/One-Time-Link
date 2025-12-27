<template>
  <div class="link-manage-page">
    <a-card title="链接管理">
      <!-- 筛选区域 -->
      <div class="filter-section">
        <a-space wrap>
          <a-select v-model:value="filters.status" placeholder="状态筛选" style="width: 120px" allowClear>
            <a-select-option :value="1">有效</a-select-option>
            <a-select-option :value="2">已使用</a-select-option>
            <a-select-option :value="3">已过期</a-select-option>
            <a-select-option :value="4">已撤销</a-select-option>
          </a-select>
          <a-select v-model:value="filters.content_type" placeholder="类型筛选" style="width: 120px" allowClear>
            <a-select-option value="secret_share">密文分享</a-select-option>
            <a-select-option value="email_verify">邮箱验证</a-select-option>
            <a-select-option value="password_reset">密码重置</a-select-option>
            <a-select-option value="magic_login">魔法登录</a-select-option>
            <a-select-option value="file_download">文件下载</a-select-option>
          </a-select>
          <a-input-search
            v-model:value="filters.keyword"
            placeholder="搜索Token"
            style="width: 200px"
            @search="handleSearch"
          />
          <a-button type="primary" @click="handleSearch">搜索</a-button>
          <a-button @click="handleReset">重置</a-button>
        </a-space>
      </div>

      <!-- 数据表格 -->
      <a-table
        :columns="columns"
        :data-source="links"
        :loading="loading"
        :pagination="pagination"
        row-key="id"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-tag :color="formatStatus(record.status).color">
              {{ formatStatus(record.status).text }}
            </a-tag>
          </template>
          <template v-else-if="column.key === 'content_type'">
            {{ formatContentType(record.content_type) }}
          </template>
          <template v-else-if="column.key === 'visits'">
            <a-progress
              :percent="(record.current_visits / record.max_visits) * 100"
              :format="() => `${record.current_visits}/${record.max_visits}`"
              size="small"
              :status="record.current_visits >= record.max_visits ? 'exception' : 'active'"
            />
          </template>
          <template v-else-if="column.key === 'expire_time'">
            {{ formatExpireTime(record.expire_time) }}
          </template>
          <template v-else-if="column.key === 'created_at'">
            {{ formatTime(record.created_at) }}
          </template>
          <template v-else-if="column.key === 'action'">
            <a-space>
              <a-button type="link" size="small" @click="handleViewDetail(record)">详情</a-button>
              <a-button type="link" size="small" @click="handleCopyLink(record)">复制</a-button>
              <a-popconfirm
                v-if="record.status === 1"
                title="确定要撤销此链接吗？"
                @confirm="handleRevoke(record)"
              >
                <a-button type="link" size="small" danger>撤销</a-button>
              </a-popconfirm>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { storeToRefs } from 'pinia'
import { useOnetimeStore } from '@/store/modules/onetime'
import { formatStatus, formatContentType, formatExpireTime, formatTime } from '@/utils/format'
import type { OnetimeLink } from '@/api/types'

const router = useRouter()
const store = useOnetimeStore()
const { links, loading, total } = storeToRefs(store)

const filters = reactive({
  status: undefined as number | undefined,
  content_type: undefined as string | undefined,
  keyword: '',
})

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (t: number) => `共 ${t} 条`,
})

const columns = [
  { title: 'Token', dataIndex: 'token', key: 'token', ellipsis: true, width: 200 },
  { title: '类型', dataIndex: 'content_type', key: 'content_type', width: 100 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 80 },
  { title: '访问次数', key: 'visits', width: 150 },
  { title: '过期时间', dataIndex: 'expire_time', key: 'expire_time', width: 180 },
  { title: '创建时间', dataIndex: 'created_at', key: 'created_at', width: 180 },
  { title: '操作', key: 'action', width: 160, fixed: 'right' as const },
]

const fetchData = async () => {
  const data = await store.fetchLinks({
    page: pagination.current,
    page_size: pagination.pageSize,
    ...filters,
  })
  pagination.total = data.total
}

const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

const handleReset = () => {
  filters.status = undefined
  filters.content_type = undefined
  filters.keyword = ''
  pagination.current = 1
  fetchData()
}

const handleTableChange = (pag: any) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

const handleViewDetail = (record: OnetimeLink) => {
  router.push(`/detail/${record.id}`)
}

const handleCopyLink = async (record: OnetimeLink) => {
  // [OTL] 始终使用当前域名生成链接
  const url = `${window.location.origin}/verify/${record.token}`
  try {
    await navigator.clipboard.writeText(url)
    message.success('链接已复制')
  } catch {
    message.error('复制失败')
  }
}

const handleRevoke = async (record: OnetimeLink) => {
  try {
    await store.revokeLink(record.id)
    message.success('链接已撤销')
  } catch {
    message.error('撤销失败')
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.filter-section {
  margin-bottom: 16px;
}
</style>
