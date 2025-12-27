import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    children: [
      {
        path: '',
        name: 'Home',
        component: () => import('@/views/Home/index.vue'),
        meta: { title: '首页' },
      },
      {
        path: 'create',
        name: 'CreateLink',
        component: () => import('@/views/CreateLink/index.vue'),
        meta: { title: '创建链接' },
      },
      {
        path: 'manage',
        name: 'LinkManage',
        component: () => import('@/views/LinkManage/index.vue'),
        meta: { title: '链接管理' },
      },
      {
        path: 'detail/:id',
        name: 'LinkDetail',
        component: () => import('@/views/LinkDetail/index.vue'),
        meta: { title: '链接详情' },
      },
    ],
  },
  {
    path: '/verify/:token',
    name: 'LinkAccess',
    component: () => import('@/views/LinkAccess/index.vue'),
    meta: { layout: 'blank', title: '访问链接' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/404.vue'),
    meta: { title: '页面不存在' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// 路由守卫
router.beforeEach((to, _from, next) => {
  const title = to.meta.title as string
  document.title = title ? `${title} - ${import.meta.env.VITE_APP_TITLE}` : import.meta.env.VITE_APP_TITLE
  next()
})

export default router
