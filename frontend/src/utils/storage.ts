const PREFIX = 'otl_'

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
