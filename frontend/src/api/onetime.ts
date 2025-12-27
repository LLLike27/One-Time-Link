import { request } from './request'
import type {
  CreateLinkParams,
  OnetimeLink,
  AccessLog,
  PaginationParams,
  PaginationResponse,
  Statistics,
} from './types'

export const onetimeApi = {
  // 创建链接
  createLink(params: CreateLinkParams): Promise<OnetimeLink> {
    return request.post('/onetime/create', params)
  },

  // 验证Token（不消费）
  verifyToken(token: string): Promise<{ valid: boolean; link?: OnetimeLink }> {
    return request.get(`/onetime/verify/${token}`)
  },

  // 访问链接（消费Token）
  accessLink(token: string): Promise<{ content_type: string; content_data: any }> {
    return request.get(`/onetime/access/${token}`)
  },

  // 撤销链接
  revokeLink(id: number): Promise<void> {
    return request.post('/onetime/revoke', { id })
  },

  // 延长过期时间
  extendExpire(id: number, extra_seconds: number): Promise<OnetimeLink> {
    return request.post('/onetime/extend', { id, extra_seconds })
  },

  // 获取链接列表
  getLinkList(
    params?: PaginationParams & {
      status?: number
      content_type?: string
      keyword?: string
    }
  ): Promise<PaginationResponse<OnetimeLink>> {
    return request.get('/onetime/list', { params })
  },

  // 获取链接详情
  getLinkDetail(id: number): Promise<OnetimeLink & { logs?: AccessLog[] }> {
    return request.get(`/onetime/detail/${id}`)
  },

  // 获取访问日志
  getAccessLogs(
    params?: PaginationParams & {
      link_id?: number
      token?: string
      start_time?: string
      end_time?: string
    }
  ): Promise<PaginationResponse<AccessLog>> {
    return request.get('/onetime/logs', { params })
  },

  // 获取统计数据
  getStatistics(): Promise<Statistics> {
    return request.get('/onetime/statistics')
  },
}

export default onetimeApi
