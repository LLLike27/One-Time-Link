import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/zh-cn'

dayjs.extend(relativeTime)
dayjs.locale('zh-cn')

// 格式化时间
export function formatTime(time: string | number | Date, format = 'YYYY-MM-DD HH:mm:ss'): string {
  if (!time) return '-'
  return dayjs(time).format(format)
}

// 相对时间
export function formatRelativeTime(time: string | number | Date): string {
  if (!time) return '-'
  return dayjs(time).fromNow()
}

// 格式化过期时间（Unix时间戳）
export function formatExpireTime(timestamp: number): string {
  if (!timestamp || timestamp === 0) {
    return '永不过期'
  }
  const now = dayjs()
  const expire = dayjs.unix(timestamp)
  if (expire.isBefore(now)) {
    return '已过期'
  }
  return expire.format('YYYY-MM-DD HH:mm:ss')
}

// 计算剩余时间
export function getRemainingTime(timestamp: number): { days: number; hours: number; minutes: number; seconds: number; expired: boolean } {
  if (!timestamp || timestamp === 0) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: false }
  }
  const now = Math.floor(Date.now() / 1000)
  const remaining = timestamp - now
  if (remaining <= 0) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true }
  }
  return {
    days: Math.floor(remaining / 86400),
    hours: Math.floor((remaining % 86400) / 3600),
    minutes: Math.floor((remaining % 3600) / 60),
    seconds: remaining % 60,
    expired: false,
  }
}

// 格式化状态
export function formatStatus(status: number): { text: string; color: string } {
  const statusMap: Record<number, { text: string; color: string }> = {
    1: { text: '有效', color: 'green' },
    2: { text: '已使用', color: 'orange' },
    3: { text: '已过期', color: 'red' },
    4: { text: '已撤销', color: 'default' },
  }
  return statusMap[status] || { text: '未知', color: 'default' }
}

// 格式化业务类型
export function formatContentType(type: string): string {
  const typeMap: Record<string, string> = {
    email_verify: '邮箱验证',
    password_reset: '密码重置',
    magic_login: '魔法登录',
    secret_share: '密文分享',
    file_download: '文件下载',
    qrcode_auth: '二维码验证',
    invite_code: '邀请码',
    custom: '自定义',
  }
  return typeMap[type] || type
}

// 格式化文件大小
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// 格式化访问结果
export function formatVisitResult(result: number): { text: string; color: string } {
  const resultMap: Record<number, { text: string; color: string }> = {
    1: { text: '成功', color: 'green' },
    2: { text: 'Token无效', color: 'red' },
    3: { text: '已过期', color: 'orange' },
    4: { text: '次数超限', color: 'orange' },
    5: { text: 'IP不匹配', color: 'red' },
    6: { text: '设备不匹配', color: 'red' },
    7: { text: '其他错误', color: 'default' },
  }
  return resultMap[result] || { text: '未知', color: 'default' }
}
