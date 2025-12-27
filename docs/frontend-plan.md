# Vue 3 前端开发计划

## 📋 项目概述

基于 Vue 3 + TypeScript + Vite 实现一次性链接功能的前端界面，提供链接创建、管理、访问等交互功能。

### 技术栈
- **框架**: Vue 3.x (Composition API)
- **语言**: TypeScript 4.x+
- **构建工具**: Vite 4.x
- **UI框架**: Ant Design Vue 4.x
- **状态管理**: Pinia
- **路由**: Vue Router 4.x
- **HTTP客户端**: Axios
- **代码规范**: ESLint + Prettier

---

## 🎯 开发任务清单

### 阶段一：环境搭建与项目初始化 ⏱️ 预计1.5小时

#### 1.1 项目创建（如基于现有项目则跳过）
```bash
# 使用Vite创建Vue3项目
npm create vite@latest onetime-link-frontend -- --template vue-ts

cd onetime-link-frontend
npm install
```

#### 1.2 安装核心依赖
- [ ] 安装UI框架
  ```bash
  npm install ant-design-vue@4.x
  npm install @ant-design/icons-vue
  ```

- [ ] 安装路由和状态管理
  ```bash
  npm install vue-router@4 pinia
  ```

- [ ] 安装HTTP客户端
  ```bash
  npm install axios
  ```

- [ ] 安装工具库
  ```bash
  npm install dayjs          # 时间处理
  npm install clipboard      # 剪贴板操作
  npm install qrcode.vue3    # 二维码生成
  npm install crypto-js      # 前端加密（可选）
  ```

- [ ] 安装开发工具
  ```bash
  npm install -D @types/node
  npm install -D unplugin-auto-import  # 自动导入
  npm install -D unplugin-vue-components  # 组件自动导入
  ```

#### 1.3 配置文件设置

##### 1.3.1 Vite配置 `vite.config.ts`
- [ ] 配置路径别名 `@`
- [ ] 配置代理（开发环境）
- [ ] 配置自动导入插件
- [ ] 配置环境变量

##### 1.3.2 TypeScript配置 `tsconfig.json`
- [ ] 启用严格模式
- [ ] 配置路径映射

##### 1.3.3 环境变量
- [ ] 创建 `.env.development`
  ```env
  VITE_API_BASE_URL=http://localhost:8080/api
  VITE_APP_TITLE=一次性链接管理系统
  ```

- [ ] 创建 `.env.production`
  ```env
  VITE_API_BASE_URL=https://api.example.com
  VITE_APP_TITLE=一次性链接管理系统
  ```

---

### 阶段二：基础架构搭建 ⏱️ 预计3小时

#### 2.1 项目目录结构

```
src/
├── api/                    # API接口定义
│   ├── types.ts           # 接口类型定义
│   ├── request.ts         # Axios封装
│   └── onetime.ts         # 一次性链接接口
├── assets/                # 静态资源
│   ├── styles/           # 全局样式
│   └── images/           # 图片资源
├── components/            # 公共组件
│   ├── LinkCard.vue      # 链接卡片组件
│   ├── QRCodeModal.vue   # 二维码弹窗组件
│   └── CopyButton.vue    # 复制按钮组件
├── layouts/               # 布局组件
│   ├── DefaultLayout.vue # 默认布局
│   └── BlankLayout.vue   # 空白布局
├── router/                # 路由配置
│   └── index.ts
├── store/                 # 状态管理
│   ├── index.ts          # Pinia实例
│   └── modules/
│       └── onetime.ts    # 一次性链接Store
├── utils/                 # 工具函数
│   ├── storage.ts        # 本地存储封装
│   ├── format.ts         # 格式化工具
│   └── validate.ts       # 验证工具
├── views/                 # 页面组件
│   ├── Home/             # 首页
│   ├── CreateLink/       # 创建链接
│   ├── LinkManage/       # 链接管理
│   ├── LinkAccess/       # 访问链接
│   └── LinkDetail/       # 链接详情
├── App.vue
└── main.ts
```

#### 2.2 Axios封装

**文件**: `src/api/request.ts`

- [ ] 创建Axios实例
- [ ] 配置请求拦截器
  - [ ] 添加Token（如需要）
  - [ ] 添加时间戳防止缓存
- [ ] 配置响应拦截器
  - [ ] 统一错误处理
  - [ ] 状态码处理（401、403、500等）
  - [ ] 消息提示
- [ ] 封装常用请求方法（GET、POST、PUT、DELETE）

示例代码：
```typescript
import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'
import { message } from 'ant-design-vue'

const service: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 10000
})

// 请求拦截器
service.interceptors.request.use(
  config => {
    // 添加Token
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code !== 200) {
      message.error(res.message || '请求失败')
      return Promise.reject(new Error(res.message || 'Error'))
    }
    return res.data
  },
  error => {
    if (error.response?.status === 401) {
      message.error('未授权，请重新登录')
      // 跳转登录页
    } else if (error.response?.status === 429) {
      message.error('访问频率过高，请稍后再试')
    } else {
      message.error(error.message || '网络错误')
    }
    return Promise.reject(error)
  }
)

export default service
```

#### 2.3 API接口定义

**文件**: `src/api/types.ts`

- [ ] 定义接口类型
  - [ ] 链接创建参数
  - [ ] 链接信息
  - [ ] 访问日志
  - [ ] 统计数据

示例代码：
```typescript
// 链接类型
export type ContentType =
  | 'email_verify'
  | 'password_reset'
  | 'magic_login'
  | 'secret_share'
  | 'file_download'
  | 'custom'

// 链接状态
export enum LinkStatus {
  ACTIVE = 1,
  USED = 2,
  EXPIRED = 3,
  REVOKED = 4
}

// 创建链接参数
export interface CreateLinkParams {
  content_type: ContentType
  content_data: any
  max_visits?: number
  expire_time?: number
  bind_ip?: boolean
  notify_on_visit?: boolean
  metadata?: Record<string, any>
}

// 链接信息
export interface OnetimeLink {
  id: number
  token: string
  content_type: ContentType
  max_visits: number
  current_visits: number
  expire_time: number
  status: LinkStatus
  created_at: string
  link_url: string
}

// 访问日志
export interface AccessLog {
  id: number
  visit_time: string
  ip_address: string
  user_agent: string
  visit_result: number
  geo_country?: string
  geo_city?: string
}
```

**文件**: `src/api/onetime.ts`

- [ ] 实现API接口方法
  - [ ] `createLink()` - 创建链接
  - [ ] `verifyToken()` - 验证Token
  - [ ] `accessLink()` - 访问链接
  - [ ] `revokeLink()` - 撤销链接
  - [ ] `getLinkList()` - 获取链接列表
  - [ ] `getLinkDetail()` - 获取链接详情
  - [ ] `getAccessLogs()` - 获取访问日志
  - [ ] `getStatistics()` - 获取统计数据

示例代码：
```typescript
import request from './request'
import type { CreateLinkParams, OnetimeLink, AccessLog } from './types'

export const onetimeApi = {
  // 创建链接
  createLink(params: CreateLinkParams) {
    return request.post<OnetimeLink>('/onetime/create', params)
  },

  // 验证Token
  verifyToken(token: string) {
    return request.get(`/onetime/verify/${token}`)
  },

  // 访问链接
  accessLink(token: string) {
    return request.get(`/onetime/access/${token}`)
  },

  // 撤销链接
  revokeLink(id: number) {
    return request.post('/onetime/revoke', { id })
  },

  // 获取链接列表
  getLinkList(params: any) {
    return request.get('/onetime/list', { params })
  },

  // 获取链接详情
  getLinkDetail(id: number) {
    return request.get(`/onetime/detail/${id}`)
  },

  // 获取访问日志
  getAccessLogs(params: any) {
    return request.get('/onetime/logs', { params })
  },

  // 获取统计数据
  getStatistics() {
    return request.get('/onetime/statistics')
  }
}
```

#### 2.4 Pinia Store

**文件**: `src/store/modules/onetime.ts`

- [ ] 定义State
  - [ ] 链接列表
  - [ ] 当前链接详情
  - [ ] 加载状态
- [ ] 定义Actions
  - [ ] 创建链接
  - [ ] 获取链接列表
  - [ ] 获取链接详情
  - [ ] 撤销链接
- [ ] 定义Getters
  - [ ] 过滤有效链接
  - [ ] 统计数据

示例代码：
```typescript
import { defineStore } from 'pinia'
import { onetimeApi } from '@/api/onetime'
import type { OnetimeLink } from '@/api/types'

export const useOnetimeStore = defineStore('onetime', {
  state: () => ({
    links: [] as OnetimeLink[],
    currentLink: null as OnetimeLink | null,
    loading: false
  }),

  getters: {
    activeLinks: (state) => {
      return state.links.filter(link => link.status === 1)
    },
    totalVisits: (state) => {
      return state.links.reduce((sum, link) => sum + link.current_visits, 0)
    }
  },

  actions: {
    async createLink(params: CreateLinkParams) {
      this.loading = true
      try {
        const link = await onetimeApi.createLink(params)
        this.links.unshift(link)
        return link
      } finally {
        this.loading = false
      }
    },

    async fetchLinks(params?: any) {
      this.loading = true
      try {
        const data = await onetimeApi.getLinkList(params)
        this.links = data.list
      } finally {
        this.loading = false
      }
    },

    async fetchLinkDetail(id: number) {
      this.loading = true
      try {
        this.currentLink = await onetimeApi.getLinkDetail(id)
      } finally {
        this.loading = false
      }
    },

    async revokeLink(id: number) {
      await onetimeApi.revokeLink(id)
      const index = this.links.findIndex(link => link.id === id)
      if (index > -1) {
        this.links[index].status = 4 // REVOKED
      }
    }
  }
})
```

#### 2.5 路由配置

**文件**: `src/router/index.ts`

- [ ] 定义路由规则
- [ ] 配置路由守卫（如需要）
- [ ] 配置404页面

示例代码：
```typescript
import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('@/layouts/DefaultLayout.vue'),
      children: [
        {
          path: '',
          name: 'Home',
          component: () => import('@/views/Home/index.vue'),
          meta: { title: '首页' }
        },
        {
          path: 'create',
          name: 'CreateLink',
          component: () => import('@/views/CreateLink/index.vue'),
          meta: { title: '创建链接' }
        },
        {
          path: 'manage',
          name: 'LinkManage',
          component: () => import('@/views/LinkManage/index.vue'),
          meta: { title: '链接管理' }
        },
        {
          path: 'detail/:id',
          name: 'LinkDetail',
          component: () => import('@/views/LinkDetail/index.vue'),
          meta: { title: '链接详情' }
        }
      ]
    },
    {
      path: '/verify/:token',
      name: 'LinkAccess',
      component: () => import('@/views/LinkAccess/index.vue'),
      meta: { layout: 'blank', title: '访问链接' }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('@/views/404.vue')
    }
  ]
})

// 路由守卫
router.beforeEach((to, from, next) => {
  document.title = `${to.meta.title || ''} - ${import.meta.env.VITE_APP_TITLE}`
  next()
})

export default router
```

---

### 阶段三：公共组件开发 ⏱️ 预计3小时

#### 3.1 链接卡片组件

**文件**: `src/components/LinkCard.vue`

- [ ] 功能需求
  - [ ] 显示链接基本信息（类型、状态、创建时间）
  - [ ] 显示访问次数进度条
  - [ ] 显示剩余有效时间
  - [ ] 复制链接按钮
  - [ ] 二维码按钮
  - [ ] 撤销按钮
  - [ ] 查看详情按钮

- [ ] UI设计
  - [ ] 卡片布局
  - [ ] 状态标签（不同状态不同颜色）
  - [ ] 图标展示
  - [ ] 悬停效果

#### 3.2 复制按钮组件

**文件**: `src/components/CopyButton.vue`

- [ ] 功能需求
  - [ ] 点击复制文本到剪贴板
  - [ ] 复制成功提示
  - [ ] 支持自定义文案

- [ ] 实现方式
  - [ ] 使用 `clipboard` 库
  - [ ] 成功/失败反馈

示例代码：
```vue
<template>
  <a-button @click="handleCopy" :icon="h(CopyOutlined)">
    {{ copied ? '已复制' : '复制链接' }}
  </a-button>
</template>

<script setup lang="ts">
import { ref, h } from 'vue'
import { message } from 'ant-design-vue'
import { CopyOutlined } from '@ant-design/icons-vue'
import Clipboard from 'clipboard'

interface Props {
  text: string
}

const props = defineProps<Props>()
const copied = ref(false)

const handleCopy = () => {
  const clipboard = new Clipboard('.copy-btn', {
    text: () => props.text
  })

  clipboard.on('success', () => {
    copied.value = true
    message.success('复制成功')
    setTimeout(() => {
      copied.value = false
    }, 2000)
    clipboard.destroy()
  })

  clipboard.on('error', () => {
    message.error('复制失败，请手动复制')
    clipboard.destroy()
  })

  clipboard.onClick({ currentTarget: document.querySelector('.copy-btn') })
}
</script>
```

#### 3.3 二维码弹窗组件

**文件**: `src/components/QRCodeModal.vue`

- [ ] 功能需求
  - [ ] 显示链接二维码
  - [ ] 支持下载二维码
  - [ ] 可自定义二维码尺寸

- [ ] 实现方式
  - [ ] 使用 `qrcode.vue3` 库
  - [ ] Modal弹窗展示

示例代码：
```vue
<template>
  <a-modal v-model:open="visible" title="链接二维码" @ok="handleDownload">
    <div class="qrcode-container">
      <qrcode-vue :value="url" :size="size" level="H" />
      <p class="url-text">{{ url }}</p>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import QrcodeVue from 'qrcode.vue3'

interface Props {
  url: string
  size?: number
}

const props = withDefaults(defineProps<Props>(), {
  size: 256
})

const visible = ref(false)

const open = () => {
  visible.value = true
}

const handleDownload = () => {
  // 下载二维码逻辑
  const canvas = document.querySelector('canvas')
  if (canvas) {
    const url = canvas.toDataURL('image/png')
    const a = document.createElement('a')
    a.href = url
    a.download = 'qrcode.png'
    a.click()
  }
}

defineExpose({ open })
</script>
```

#### 3.4 倒计时组件

**文件**: `src/components/CountDown.vue`

- [ ] 功能需求
  - [ ] 显示剩余时间（天、时、分、秒）
  - [ ] 过期后显示"已过期"
  - [ ] 实时更新

---

### 阶段四：页面开发 ⏱️ 预计6小时

#### 4.1 首页 `src/views/Home/index.vue`

- [ ] 功能需求
  - [ ] 快速创建链接入口
  - [ ] 最近创建的链接列表
  - [ ] 统计数据展示（总链接数、总访问数等）
  - [ ] 功能介绍

- [ ] UI布局
  - [ ] 顶部Banner
  - [ ] 卡片式统计数据
  - [ ] 链接列表（使用LinkCard组件）

#### 4.2 创建链接页 `src/views/CreateLink/index.vue`

- [ ] 功能需求
  - [ ] 表单输入
    - [ ] 业务类型选择（下拉框）
    - [ ] 业务数据输入（根据类型动态表单）
    - [ ] 访问次数设置（默认1次）
    - [ ] 过期时间设置（时间选择器）
    - [ ] IP绑定开关
    - [ ] 访问通知开关
  - [ ] 表单验证
  - [ ] 提交创建
  - [ ] 创建成功后展示链接

- [ ] UI设计
  - [ ] 分步表单或单页表单
  - [ ] 实时预览
  - [ ] 成功提示弹窗（包含链接、二维码、复制按钮）

示例代码结构：
```vue
<template>
  <div class="create-link-container">
    <a-card title="创建一次性链接">
      <a-form :model="formState" :rules="rules" @finish="handleSubmit">
        <a-form-item label="业务类型" name="content_type">
          <a-select v-model:value="formState.content_type">
            <a-select-option value="email_verify">邮箱验证</a-select-option>
            <a-select-option value="password_reset">密码重置</a-select-option>
            <a-select-option value="magic_login">魔法登录</a-select-option>
            <a-select-option value="secret_share">密文分享</a-select-option>
            <a-select-option value="file_download">文件下载</a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="业务数据" name="content_data">
          <a-textarea
            v-model:value="formState.content_data"
            :rows="4"
            placeholder="请输入业务相关数据（JSON格式）"
          />
        </a-form-item>

        <a-form-item label="最大访问次数" name="max_visits">
          <a-input-number v-model:value="formState.max_visits" :min="1" :max="100" />
        </a-form-item>

        <a-form-item label="过期时间" name="expire_time">
          <a-date-picker
            v-model:value="formState.expireDate"
            show-time
            format="YYYY-MM-DD HH:mm:ss"
          />
        </a-form-item>

        <a-form-item label="高级选项">
          <a-space direction="vertical">
            <a-switch v-model:checked="formState.bind_ip" /> 绑定IP地址
            <a-switch v-model:checked="formState.notify_on_visit" /> 访问时通知
          </a-space>
        </a-form-item>

        <a-form-item>
          <a-button type="primary" html-type="submit" :loading="loading">
            创建链接
          </a-button>
        </a-form-item>
      </a-form>
    </a-card>

    <!-- 成功弹窗 -->
    <a-modal v-model:open="successModalVisible" title="链接创建成功" @ok="handleCopyLink">
      <div class="success-content">
        <qrcode-vue :value="createdLink.link_url" :size="200" />
        <p class="link-url">{{ createdLink.link_url }}</p>
        <copy-button :text="createdLink.link_url" />
      </div>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useOnetimeStore } from '@/store/modules/onetime'
import type { CreateLinkParams } from '@/api/types'

const store = useOnetimeStore()
const loading = ref(false)
const successModalVisible = ref(false)
const createdLink = ref<any>(null)

const formState = reactive<CreateLinkParams>({
  content_type: 'secret_share',
  content_data: '',
  max_visits: 1,
  expire_time: 0,
  bind_ip: false,
  notify_on_visit: false
})

const handleSubmit = async () => {
  loading.value = true
  try {
    createdLink.value = await store.createLink(formState)
    successModalVisible.value = true
  } finally {
    loading.value = false
  }
}
</script>
```

#### 4.3 链接管理页 `src/views/LinkManage/index.vue`

- [ ] 功能需求
  - [ ] 链接列表展示（表格或卡片）
  - [ ] 筛选功能
    - [ ] 按状态筛选
    - [ ] 按类型筛选
    - [ ] 按时间范围筛选
  - [ ] 搜索功能（Token搜索）
  - [ ] 分页
  - [ ] 批量操作
    - [ ] 批量撤销
    - [ ] 批量删除
  - [ ] 单个操作
    - [ ] 查看详情
    - [ ] 复制链接
    - [ ] 撤销
    - [ ] 延长有效期

- [ ] UI设计
  - [ ] 顶部筛选栏
  - [ ] 表格或卡片列表
  - [ ] 操作按钮组

#### 4.4 链接详情页 `src/views/LinkDetail/index.vue`

- [ ] 功能需求
  - [ ] 链接基本信息展示
  - [ ] 访问日志列表
    - [ ] 时间、IP、User-Agent、地理位置
    - [ ] 访问结果
  - [ ] 访问统计图表
    - [ ] 访问趋势图（折线图）
    - [ ] 地理分布图（地图）
  - [ ] 操作按钮
    - [ ] 复制链接
    - [ ] 撤销
    - [ ] 延长有效期

- [ ] UI设计
  - [ ] 顶部信息卡片
  - [ ] 统计图表区域
  - [ ] 访问日志表格

#### 4.5 访问链接页 `src/views/LinkAccess/index.vue`

- [ ] 功能需求
  - [ ] 自动验证Token
  - [ ] 显示加载状态
  - [ ] 验证成功后展示内容
  - [ ] 验证失败提示
    - [ ] Token不存在
    - [ ] 已过期
    - [ ] 访问次数超限
    - [ ] IP不匹配
  - [ ] 根据业务类型执行不同操作
    - [ ] `email_verify`: 自动验证并跳转
    - [ ] `password_reset`: 跳转到重置密码页
    - [ ] `secret_share`: 展示密文内容
    - [ ] `file_download`: 触发文件下载

- [ ] UI设计
  - [ ] 居中加载动画
  - [ ] 成功/失败反馈页面
  - [ ] 友好的错误提示

示例代码结构：
```vue
<template>
  <div class="access-container">
    <a-spin v-if="loading" size="large" />

    <a-result v-else-if="error" status="error" :title="errorTitle" :sub-title="errorMessage">
      <template #extra>
        <a-button type="primary" @click="goHome">返回首页</a-button>
      </template>
    </a-result>

    <div v-else-if="linkData" class="content">
      <a-result status="success" title="验证成功">
        <template #extra>
          <div v-if="linkData.content_type === 'secret_share'">
            <a-card title="密文内容">
              <pre>{{ linkData.content_data }}</pre>
            </a-card>
          </div>
          <div v-else-if="linkData.content_type === 'file_download'">
            <a-button type="primary" @click="downloadFile">下载文件</a-button>
          </div>
        </template>
      </a-result>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { onetimeApi } from '@/api/onetime'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref(false)
const errorTitle = ref('')
const errorMessage = ref('')
const linkData = ref<any>(null)

onMounted(async () => {
  const token = route.params.token as string
  try {
    linkData.value = await onetimeApi.accessLink(token)
  } catch (err: any) {
    error.value = true
    errorTitle.value = '访问失败'
    errorMessage.value = err.message || '链接无效或已过期'
  } finally {
    loading.value = false
  }
})

const goHome = () => {
  router.push('/')
}
</script>
```

---

### 阶段五：工具函数开发 ⏱️ 预计1.5小时

#### 5.1 本地存储封装

**文件**: `src/utils/storage.ts`

- [ ] `setItem()` - 存储数据
- [ ] `getItem()` - 获取数据
- [ ] `removeItem()` - 删除数据
- [ ] `clear()` - 清空存储

#### 5.2 格式化工具

**文件**: `src/utils/format.ts`

- [ ] `formatTime()` - 时间格式化
- [ ] `formatStatus()` - 状态文本转换
- [ ] `formatContentType()` - 业务类型文本转换
- [ ] `formatFileSize()` - 文件大小格式化

#### 5.3 验证工具

**文件**: `src/utils/validate.ts`

- [ ] `isValidUrl()` - URL验证
- [ ] `isValidEmail()` - 邮箱验证
- [ ] `isValidJSON()` - JSON格式验证

---

### 阶段六：样式美化与优化 ⏱️ 预计2小时

#### 6.1 全局样式

**文件**: `src/assets/styles/global.less`

- [ ] 定义全局变量（颜色、字体、间距）
- [ ] 重置默认样式
- [ ] 公共类名（居中、间距、文本等）

#### 6.2 响应式适配

- [ ] 移动端适配（媒体查询）
- [ ] 平板适配
- [ ] 桌面端优化

#### 6.3 动画效果

- [ ] 页面过渡动画
- [ ] 加载动画
- [ ] 悬停效果

---

### 阶段七：测试与优化 ⏱️ 预计2小时

#### 7.1 单元测试

- [ ] 安装测试框架
  ```bash
  npm install -D vitest @vue/test-utils
  ```

- [ ] 编写组件测试
  - [ ] CopyButton组件测试
  - [ ] LinkCard组件测试

#### 7.2 E2E测试（可选）

- [ ] 安装 Playwright 或 Cypress
- [ ] 编写端到端测试场景

#### 7.3 性能优化

- [ ] 路由懒加载（已配置）
- [ ] 组件按需引入
- [ ] 图片懒加载
- [ ] 代码分割

#### 7.4 浏览器兼容性测试

- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

---

### 阶段八：打包与部署 ⏱️ 预计1小时

#### 8.1 生产环境打包

```bash
npm run build
```

- [ ] 检查打包产物
- [ ] 分析包大小（使用 `rollup-plugin-visualizer`）
- [ ] 优化chunk大小

#### 8.2 部署配置

##### 8.2.1 Nginx配置
```nginx
server {
    listen 80;
    server_name example.com;

    root /var/www/onetime-link;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

##### 8.2.2 CI/CD配置（可选）
- [ ] 配置GitHub Actions
- [ ] 自动构建
- [ ] 自动部署

---

## 📂 最终目录结构

```
src/
├── api/
│   ├── types.ts                 # 类型定义
│   ├── request.ts               # Axios封装
│   └── onetime.ts               # API接口
├── assets/
│   ├── styles/
│   │   ├── global.less          # 全局样式
│   │   └── variables.less       # 样式变量
│   └── images/
├── components/
│   ├── LinkCard.vue             # 链接卡片
│   ├── CopyButton.vue           # 复制按钮
│   ├── QRCodeModal.vue          # 二维码弹窗
│   └── CountDown.vue            # 倒计时
├── layouts/
│   ├── DefaultLayout.vue        # 默认布局
│   └── BlankLayout.vue          # 空白布局
├── router/
│   └── index.ts                 # 路由配置
├── store/
│   ├── index.ts                 # Pinia实例
│   └── modules/
│       └── onetime.ts           # Store模块
├── utils/
│   ├── storage.ts               # 本地存储
│   ├── format.ts                # 格式化工具
│   └── validate.ts              # 验证工具
├── views/
│   ├── Home/
│   │   └── index.vue            # 首页
│   ├── CreateLink/
│   │   └── index.vue            # 创建链接
│   ├── LinkManage/
│   │   └── index.vue            # 链接管理
│   ├── LinkDetail/
│   │   └── index.vue            # 链接详情
│   ├── LinkAccess/
│   │   └── index.vue            # 访问链接
│   └── 404.vue                  # 404页面
├── App.vue
└── main.ts
```

---

## ✅ 验收标准

### 功能验收
- [ ] 所有页面正常渲染
- [ ] 创建链接功能正常
- [ ] 链接访问验证正确
- [ ] 复制链接功能正常
- [ ] 二维码生成正确
- [ ] 撤销链接功能正常
- [ ] 筛选和搜索功能正常
- [ ] 访问日志展示完整

### UI/UX验收
- [ ] 设计符合Ant Design规范
- [ ] 响应式布局正常
- [ ] 交互流畅无卡顿
- [ ] 错误提示友好
- [ ] 加载状态明确

### 性能验收
- [ ] 首屏加载时间 < 2s
- [ ] 路由切换流畅
- [ ] 打包体积合理（< 500KB Gzipped）

### 兼容性验收
- [ ] 支持主流浏览器最新版本
- [ ] 移动端适配良好

---

## 📚 参考资源

- [Vue 3 官方文档](https://cn.vuejs.org/)
- [Ant Design Vue 文档](https://antdv.com/)
- [Pinia 文档](https://pinia.vuejs.org/zh/)
- [Vite 文档](https://cn.vitejs.dev/)

---

## 🔗 相关文档

- [README.md](./README.md) - 核心功能设计
- [database-design.md](./database-design.md) - 数据库设计
- [backend-plan.md](./backend-plan.md) - 后端开发计划

---

## 📝 开发日志

| 日期 | 阶段 | 完成情况 | 备注 |
|------|------|---------|------|
| - | - | - | - |
