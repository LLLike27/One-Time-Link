// 链接类型
export type ContentType =
  | 'email_verify'
  | 'password_reset'
  | 'magic_login'
  | 'secret_share'
  | 'file_download'
  | 'qrcode_auth'
  | 'invite_code'
  | 'custom'

// 链接状态
export enum LinkStatus {
  ACTIVE = 1,
  USED = 2,
  EXPIRED = 3,
  REVOKED = 4,
}

// 访问结果
export enum VisitResult {
  SUCCESS = 1,
  TOKEN_INVALID = 2,
  EXPIRED = 3,
  VISITS_EXCEEDED = 4,
  IP_MISMATCH = 5,
  DEVICE_MISMATCH = 6,
  OTHER_ERROR = 7,
}

// 创建链接参数
export interface CreateLinkParams {
  content_type: ContentType
  content_data: string | Record<string, any>
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
  user_id?: number
  content_type: ContentType
  content_data?: string
  max_visits: number
  current_visits: number
  expire_time: number
  bind_ip?: string
  status: LinkStatus
  notify_on_visit: boolean
  metadata?: Record<string, any>
  created_at: string
  updated_at: string
  link_url?: string
}

// 访问日志
export interface AccessLog {
  id: number
  link_id: number
  token: string
  visit_time: string
  ip_address: string
  user_agent?: string
  device_fingerprint?: string
  geo_country?: string
  geo_region?: string
  geo_city?: string
  visit_result: VisitResult
  error_message?: string
  referer?: string
  extra_data?: Record<string, any>
}

// 通知记录
export interface LinkNotification {
  id: number
  link_id: number
  log_id?: number
  user_id: number
  notify_type: 'email' | 'sms' | 'push' | 'webhook'
  notify_to: string
  notify_content?: string
  send_status: number
  send_time?: string
  error_message?: string
  created_at: string
}

// 分页参数
export interface PaginationParams {
  page?: number
  page_size?: number
}

// 分页响应
export interface PaginationResponse<T> {
  list: T[]
  total: number
  page: number
  page_size: number
}

// 统计数据
export interface Statistics {
  total_links: number
  active_links: number
  used_links: number
  expired_links: number
  total_visits: number
  success_visits: number
  success_rate: number
}

// API响应结构
export interface ApiResponse<T = any> {
  code: number
  message: string
  data: T
}
