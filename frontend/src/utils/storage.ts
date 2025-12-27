const PREFIX = 'otl_'
const DEVICE_ID_KEY = 'device_id'

/**
 * [OTL] 生成UUID v4
 */
function generateUUID(): string {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0
    const v = c === 'x' ? r : (r & 0x3) | 0x8
    return v.toString(16)
  })
}

/**
 * [OTL] 获取或创建设备ID
 */
export function getDeviceId(): string {
  let deviceId = localStorage.getItem(PREFIX + DEVICE_ID_KEY)
  if (!deviceId) {
    deviceId = generateUUID()
    localStorage.setItem(PREFIX + DEVICE_ID_KEY, deviceId)
    console.log('[OTL] Generated new device ID:', deviceId)
  }
  return deviceId
}

export const storage = {
  set(key: string, value: any): void {
    try {
      const data = JSON.stringify(value)
      localStorage.setItem(PREFIX + key, data)
    } catch (e) {
      console.error('[OTL] Storage set error:', e)
    }
  },

  get<T = any>(key: string, defaultValue?: T): T | undefined {
    try {
      const data = localStorage.getItem(PREFIX + key)
      if (data === null) {
        return defaultValue
      }
      return JSON.parse(data) as T
    } catch (e) {
      console.error('[OTL] Storage get error:', e)
      return defaultValue
    }
  },

  remove(key: string): void {
    localStorage.removeItem(PREFIX + key)
  },

  clear(): void {
    const keys = Object.keys(localStorage)
    keys.forEach((key) => {
      if (key.startsWith(PREFIX)) {
        localStorage.removeItem(key)
      }
    })
  },
}

export default storage
