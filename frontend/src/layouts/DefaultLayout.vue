<template>
  <a-layout class="layout">
    <a-layout-header class="header">
      <div class="header-left">
        <div class="logo" @click="router.push('/')">
          <LinkOutlined />
          <span class="logo-text">一次性链接</span>
        </div>
        <a-menu
          v-if="!isMobile"
          :selectedKeys="selectedKeys"
          class="nav-menu"
          mode="horizontal"
          :items="menuItems"
          @click="handleMenuClick"
        />
      </div>

      <a-button v-if="isMobile" class="menu-trigger" type="text" @click="drawerOpen = true">
        <MenuOutlined />
      </a-button>
    </a-layout-header>
    <a-layout-content class="content">
      <div class="container">
        <router-view />
      </div>
    </a-layout-content>
    <a-layout-footer class="footer">
      One-Time Link &copy; {{ new Date().getFullYear() }}
    </a-layout-footer>

    <a-drawer
      v-if="isMobile"
      :open="drawerOpen"
      placement="left"
      width="80%"
      class="nav-drawer"
      @update:open="drawerOpen = $event"
    >
      <div class="drawer-logo" @click="handleGoHome">
        <LinkOutlined />
        <span>一次性链接</span>
      </div>
      <a-menu
        :selectedKeys="selectedKeys"
        mode="inline"
        :items="menuItems"
        @click="handleMenuClick"
      />
    </a-drawer>
  </a-layout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { LinkOutlined, HomeOutlined, PlusOutlined, UnorderedListOutlined, MenuOutlined } from '@ant-design/icons-vue'
import type { MenuProps } from 'ant-design-vue'
import { useMediaQuery } from '@/hooks/useMediaQuery'

const router = useRouter()
const route = useRoute()

const isMobile = useMediaQuery('(max-width: 768px)')
const selectedKeys = ref<string[]>(['Home'])
const drawerOpen = ref(false)

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

watch(isMobile, (mobile) => {
  if (!mobile) {
    drawerOpen.value = false
  }
})

const handleGoHome = () => {
  drawerOpen.value = false
  router.push('/')
}

const handleMenuClick: MenuProps['onClick'] = ({ key }) => {
  drawerOpen.value = false
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
  justify-content: space-between;
  padding: 0 var(--app-container-padding);
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--app-border);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
  min-width: 0;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(0, 0, 0, 0.88);
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  user-select: none;
}

.nav-menu {
  flex: 1;
  min-width: 0;
  background: transparent;
  border-bottom: none;
}

.menu-trigger {
  display: inline-flex;
}

.content {
  padding: var(--app-container-padding);
  background: transparent;
}

.container {
  max-width: var(--app-container-max-width);
  margin: 0 auto;
}

.footer {
  text-align: center;
  color: rgba(0, 0, 0, 0.45);
  background: transparent;
}

.drawer-logo {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 16px 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
}

@media (max-width: 768px) {
  .logo {
    font-size: 16px;
  }
}
</style>
