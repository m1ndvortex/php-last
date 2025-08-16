import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

// Test basic Vue reactivity first
describe('Basic Vue Reactivity Test', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('should import Vue ref correctly', async () => {
    const { ref } = await import('vue');
    expect(typeof ref).toBe('function');
    
    const testRef = ref(0);
    expect(testRef.value).toBe(0);
    
    testRef.value = 5;
    expect(testRef.value).toBe(5);
  });

  it('should create a basic store', async () => {
    const { defineStore } = await import('pinia');
    const { ref } = await import('vue');
    
    const useTestStore = defineStore('test', () => {
      const count = ref(0);
      const increment = () => count.value++;
      
      return { count, increment };
    });
    
    const store = useTestStore();
    expect(store.count).toBe(0);
    
    store.increment();
    expect(store.count).toBe(1);
  });
});