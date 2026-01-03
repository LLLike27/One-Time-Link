import { onMounted, onUnmounted, ref } from 'vue'

export function useMediaQuery(query: string) {
  const matches = ref(false)
  let mediaQueryList: MediaQueryList | null = null

  const sync = () => {
    matches.value = mediaQueryList?.matches ?? false
  }

  const handleChange = (event: MediaQueryListEvent) => {
    matches.value = event.matches
  }

  const init = () => {
    if (typeof window === 'undefined' || !('matchMedia' in window)) return
    if (mediaQueryList) return
    mediaQueryList = window.matchMedia(query)
    sync()
  }

  init()

  onMounted(() => {
    init()
    if (!mediaQueryList) return
    mediaQueryList.addEventListener('change', handleChange)
  })

  onUnmounted(() => {
    if (!mediaQueryList) return
    mediaQueryList.removeEventListener('change', handleChange)
  })

  return matches
}
