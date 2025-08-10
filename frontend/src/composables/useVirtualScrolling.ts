import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";

interface VirtualScrollOptions {
  itemHeight: number;
  containerHeight?: number;
  buffer?: number;
  threshold?: number;
}

export function useVirtualScrolling<T>(
  items: T[],
  options: VirtualScrollOptions,
) {
  const {
    itemHeight,
    containerHeight = 400,
    buffer = 5,
    threshold = 100,
  } = options;

  const scrollTop = ref(0);
  const containerRef = ref<HTMLElement>();
  const isScrolling = ref(false);
  let scrollTimeout: NodeJS.Timeout;

  // Calculate visible range
  const visibleRange = computed(() => {
    const start = Math.floor(scrollTop.value / itemHeight);
    const visibleCount = Math.ceil(containerHeight / itemHeight);
    const end = start + visibleCount;

    return {
      start: Math.max(0, start - buffer),
      end: Math.min(items.length, end + buffer),
      visibleStart: start,
      visibleEnd: end,
    };
  });

  // Get visible items
  const visibleItems = computed(() => {
    const { start, end } = visibleRange.value;
    return items.slice(start, end).map((item, index) => ({
      item,
      index: start + index,
      top: (start + index) * itemHeight,
    }));
  });

  // Calculate total height
  const totalHeight = computed(() => items.length * itemHeight);

  // Calculate offset for visible items
  const offsetY = computed(() => visibleRange.value.start * itemHeight);

  // Handle scroll events
  const handleScroll = (event: Event) => {
    const target = event.target as HTMLElement;
    scrollTop.value = target.scrollTop;

    isScrolling.value = true;
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
      isScrolling.value = false;
    }, 150);
  };

  // Scroll to specific index
  const scrollToIndex = (
    index: number,
    behavior: ScrollBehavior = "smooth",
  ) => {
    if (!containerRef.value) return;

    const targetScrollTop = index * itemHeight;
    containerRef.value.scrollTo({
      top: targetScrollTop,
      behavior,
    });
  };

  // Scroll to top
  const scrollToTop = (behavior: ScrollBehavior = "smooth") => {
    scrollToIndex(0, behavior);
  };

  // Check if item is in view
  const isItemInView = (index: number) => {
    const { visibleStart, visibleEnd } = visibleRange.value;
    return index >= visibleStart && index <= visibleEnd;
  };

  // Get item position
  const getItemPosition = (index: number) => {
    return {
      top: index * itemHeight,
      bottom: (index + 1) * itemHeight,
    };
  };

  // Lifecycle
  onMounted(() => {
    if (containerRef.value) {
      containerRef.value.addEventListener("scroll", handleScroll, {
        passive: true,
      });
    }
  });

  onUnmounted(() => {
    if (containerRef.value) {
      containerRef.value.removeEventListener("scroll", handleScroll);
    }
    clearTimeout(scrollTimeout);
  });

  return {
    // Refs
    containerRef,
    scrollTop,
    isScrolling,

    // Computed
    visibleItems,
    visibleRange,
    totalHeight,
    offsetY,

    // Methods
    scrollToIndex,
    scrollToTop,
    isItemInView,
    getItemPosition,

    // Event handler
    handleScroll,
  };
}

// Composable for infinite scrolling
export function useInfiniteScroll<T>(
  loadMore: () => Promise<void>,
  options: {
    threshold?: number;
    disabled?: boolean;
  } = {},
) {
  const { threshold = 100, disabled = false } = options;

  const isLoading = ref(false);
  const containerRef = ref<HTMLElement>();

  const handleScroll = async (event: Event) => {
    if (disabled || isLoading.value) return;

    const target = event.target as HTMLElement;
    const { scrollTop, scrollHeight, clientHeight } = target;

    // Check if we're near the bottom
    if (scrollHeight - scrollTop - clientHeight < threshold) {
      isLoading.value = true;
      try {
        await loadMore();
      } finally {
        isLoading.value = false;
      }
    }
  };

  onMounted(() => {
    if (containerRef.value) {
      containerRef.value.addEventListener("scroll", handleScroll, {
        passive: true,
      });
    }
  });

  onUnmounted(() => {
    if (containerRef.value) {
      containerRef.value.removeEventListener("scroll", handleScroll);
    }
  });

  return {
    containerRef,
    isLoading,
    handleScroll,
  };
}
