// URL验证
export function isValidUrl(url: string): boolean {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

// 邮箱验证
export function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// JSON格式验证
export function isValidJSON(str: string): boolean {
  try {
    JSON.parse(str)
    return true
  } catch {
    return false
  }
}

// Token格式验证
export function isValidToken(token: string): boolean {
  // Token应该是32-64位的字母数字字符串
  const tokenRegex = /^[a-zA-Z0-9_-]{32,64}$/
  return tokenRegex.test(token)
}

// 手机号验证（中国）
export function isValidPhone(phone: string): boolean {
  const phoneRegex = /^1[3-9]\d{9}$/
  return phoneRegex.test(phone)
}

// 非空验证
export function isNotEmpty(value: any): boolean {
  if (value === null || value === undefined) return false
  if (typeof value === 'string') return value.trim().length > 0
  if (Array.isArray(value)) return value.length > 0
  if (typeof value === 'object') return Object.keys(value).length > 0
  return true
}

// 数字范围验证
export function isInRange(value: number, min: number, max: number): boolean {
  return value >= min && value <= max
}
