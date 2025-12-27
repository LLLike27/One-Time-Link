<template>
  <a-layout class="layout">
    <a-layout-header class="header">
      <div class="logo">
        <LinkOutlined />
        <span>一次性链接</span>
      </div>
      <a-menu
        v-model:selectedKeys="selectedKeys"
        theme="dark"
        mode="horizontal"
        :items="menuItems"
        @click="handleMenuClick"
      />
    </a-layout-header>
    <a-layout-content class="content">
      <div class="container">
        <router-view />
      </div>
    </a-layout-content>
    <a-layout-footer class="footer">
      One-Time Link &copy; {{ new Date().getFullYear() }}
    </a-layout-footer>
  </a-layout>
</template>

<script setup lang="ts">
import { ref, computed, watch} from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { LinkOutlined, HomeOutlined, PlusOutlined, UnorderedListOutlined } from '@ant-design/icons-vue'
import type { MenuProps } from 'ant-design-vue'

const router = useRouter()
const route = useRoute()

const selectedKeys = ref<string[]>(['Home'])

const menuItems = computed<MenuProps['items']>(() => [
  {
    key: 'Home',
    icon: () => h(HomeOutlined),
    label: '首页',
  },
  {
    key: 'CreateLink',
    icon: () => h(PlusOutlined),
    label: '创建链接',
  },
  {
    key: 'LinkManage',
    icon: () => h(UnorderedListOutlined),
    label: '链接管理',
  },
])

watch(
  () => route.name,
  (name) => {
    if (name) {
      selectedKeys.value = [name as string]
    }
  },
  { immediate: true }
)

const handleMenuClick: MenuProps['onClick'] = ({ key }) => {
  router.push({ name: key as string })
}
</script>

<style scoped>
.layout {
  min-height: 100vh;
}

.header {
  display: flex;
  align-items: center;
  padding: 0 24px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  margin-right: 24px;
}

.content {
  padding: 24px;
  background: #f0f2f5;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.footer {
  text-align: center;
  color: #999;
}
</style>
