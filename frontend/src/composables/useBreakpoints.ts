import { ref, computed, onMounted, onUnmounted } from "vue";

export function useBreakpoints() {
  const windowWidth = ref(0);

  const updateWidth = () => {
    windowWidth.value = window.innerWidth;
  };

  onMounted(() => {
    updateWidth();
    window.addEventListener("resize", updateWidth);
  });

  onUnmounted(() => {
    window.removeEventListener("resize", updateWidth);
  });

  // Tailwind CSS breakpoints
  const breakpoints = {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    "2xl": 1536,
  };

  const isSm = computed(() => windowWidth.value >= breakpoints.sm);
  const isMd = computed(() => windowWidth.value >= breakpoints.md);
  const isLg = computed(() => windowWidth.value >= breakpoints.lg);
  const isXl = computed(() => windowWidth.value >= breakpoints.xl);
  const is2xl = computed(() => windowWidth.value >= breakpoints["2xl"]);

  const isMobile = computed(() => windowWidth.value < breakpoints.md);
  const isTablet = computed(
    () =>
      windowWidth.value >= breakpoints.md && windowWidth.value < breakpoints.lg,
  );
  const isDesktop = computed(() => windowWidth.value >= breakpoints.lg);

  const currentBreakpoint = computed(() => {
    if (windowWidth.value >= breakpoints["2xl"]) return "2xl";
    if (windowWidth.value >= breakpoints.xl) return "xl";
    if (windowWidth.value >= breakpoints.lg) return "lg";
    if (windowWidth.value >= breakpoints.md) return "md";
    if (windowWidth.value >= breakpoints.sm) return "sm";
    return "xs";
  });

  return {
    windowWidth,
    isSm,
    isMd,
    isLg,
    isXl,
    is2xl,
    isMobile,
    isTablet,
    isDesktop,
    currentBreakpoint,
  };
}
