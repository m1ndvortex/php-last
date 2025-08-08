import { ref, onMounted, onUnmounted } from 'vue'

interface LazyLoadOptions {
  threshold?: number
  rootMargin?: string
  once?: boolean
}

export function useLazyLoading(options: LazyLoadOptions = {}) {
  const {
    threshold = 0.1,
    rootMargin = '50px',
    once = true
  } = options

  const isVisible = ref(false)
  const isLoaded = ref(false)
  const targetRef = ref<HTMLElement>()
  let observer: IntersectionObserver | null = null

  const load = () => {
    if (!isLoaded.value) {
      isLoaded.value = true
    }
  }

  const startObserving = () => {
    if (!targetRef.value || !('IntersectionObserver' in window)) {
      // Fallback for browsers without IntersectionObserver
      isVisible.value = true
      load()
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            isVisible.value = true
            load()
            
            if (once && observer) {
              observer.unobserve(entry.target)
            }
          } else if (!once) {
            isVisible.value = false
          }
        })
      },
      {
        threshold,
        rootMargin
      }
    )

    observer.observe(targetRef.value)
  }

  const stopObserving = () => {
    if (observer && targetRef.value) {
      observer.unobserve(targetRef.value)
      observer = null
    }
  }

  onMounted(() => {
    startObserving()
  })

  onUnmounted(() => {
    stopObserving()
  })

  return {
    targetRef,
    isVisible,
    isLoaded,
    load,
    startObserving,
    stopObserving
  }
}

// Composable for lazy loading images
export function useLazyImage(src: string, options: LazyLoadOptions = {}) {
  const { targetRef, isVisible, isLoaded } = useLazyLoading(options)
  const imageSrc = ref('')
  const imageError = ref(false)
  const imageLoading = ref(false)

  const loadImage = async () => {
    if (!src || isLoaded.value) return

    imageLoading.value = true
    imageError.value = false

    try {
      const img = new Image()
      img.onload = () => {
        imageSrc.value = src
        imageLoading.value = false
      }
      img.onerror = () => {
        imageError.value = true
        imageLoading.value = false
      }
      img.src = src
    } catch (error) {
      imageError.value = true
      imageLoading.value = false
    }
  }

  // Load image when visible
  const startImageLoading = () => {
    if (isVisible.value && !isLoaded.value) {
      loadImage()
    }
  }

  return {
    targetRef,
    imageSrc,
    imageError,
    imageLoading,
    isVisible,
    isLoaded,
    loadImage: startImageLoading
  }
}

// Composable for lazy loading components
export function useLazyComponent<T>(
  importFn: () => Promise<T>,
  options: LazyLoadOptions = {}
) {
  const { targetRef, isVisible, isLoaded } = useLazyLoading(options)
  const component = ref<T | null>(null)
  const componentError = ref(false)
  const componentLoading = ref(false)

  const loadComponent = async () => {
    if (isLoaded.value || componentLoading.value) return

    componentLoading.value = true
    componentError.value = false

    try {
      component.value = await importFn()
      componentLoading.value = false
    } catch (error) {
      componentError.value = true
      componentLoading.value = false
      console.error('Failed to load component:', error)
    }
  }

  // Load component when visible
  const startComponentLoading = () => {
    if (isVisible.value && !isLoaded.value) {
      loadComponent()
    }
  }

  return {
    targetRef,
    component,
    componentError,
    componentLoading,
    isVisible,
    isLoaded,
    loadComponent: startComponentLoading
  }
}