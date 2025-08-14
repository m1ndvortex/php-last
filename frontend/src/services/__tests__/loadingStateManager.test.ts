import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { LoadingStateManager } from '../loadingStateManager';

// Mock performance.now
const mockPerformanceNow = vi.fn(() => Date.now());
Object.defineProperty(window, 'performance', {
  value: { now: mockPerformanceNow },
  writable: true
});

describe('LoadingStateManager', () => {
  let manager: LoadingStateManager;

  beforeEach(() => {
    manager = new LoadingStateManager();
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('startLoading', () => {
    it('should start loading for a context', () => {
      const context = 'test-context';
      const message = 'Loading test...';
      
      manager.startLoading(context, message);
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(true);
      expect(state.value.message).toBe(message);
      expect(state.value.progress).toBe(0);
      expect(state.value.error).toBeNull();
    });

    it('should update global loading state', () => {
      const context = 'test-context';
      
      expect(manager.isAnyLoading()).toBe(false);
      
      manager.startLoading(context);
      
      expect(manager.isAnyLoading()).toBe(true);
      expect(manager.getGlobalLoadingState().value).toBe(true);
    });

    it('should set estimated time based on context', () => {
      const context = 'dashboard';
      
      manager.startLoading(context);
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.estimatedTime).toBeGreaterThan(0);
    });
  });

  describe('updateProgress', () => {
    it('should update progress for active loading', () => {
      const context = 'test-context';
      
      manager.startLoading(context);
      manager.updateProgress(context, 50, 'Half way there...');
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.progress).toBe(50);
      expect(state.value.message).toBe('Half way there...');
    });

    it('should clamp progress between 0 and 100', () => {
      const context = 'test-context';
      
      manager.startLoading(context);
      
      manager.updateProgress(context, -10);
      expect(manager.getLoadingStateReactive(context).value.progress).toBe(0);
      
      manager.updateProgress(context, 150);
      expect(manager.getLoadingStateReactive(context).value.progress).toBe(100);
    });

    it('should update estimated time based on progress', () => {
      const context = 'test-context';
      
      // Mock Date.now to control time
      let currentTime = 1000;
      Date.now = vi.fn(() => currentTime);
      
      manager.startLoading(context);
      
      // Simulate 100ms passing and 25% progress
      currentTime += 100;
      manager.updateProgress(context, 25);
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.estimatedTime).toBe(400); // 100ms / 0.25 = 400ms total
    });

    it('should not update progress for non-loading context', () => {
      const context = 'test-context';
      
      manager.updateProgress(context, 50);
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.progress).toBe(0);
    });
  });

  describe('finishLoading', () => {
    it('should finish loading successfully', async () => {
      const context = 'test-context';
      
      manager.startLoading(context);
      manager.finishLoading(context);
      
      // Wait for any async operations
      await vi.runAllTimersAsync();
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.progress).toBe(100);
      expect(state.value.error).toBeNull();
    });

    it('should finish loading with error', async () => {
      const context = 'test-context';
      const error = new Error('Loading failed');
      
      manager.startLoading(context);
      manager.finishLoading(context, error);
      
      await vi.runAllTimersAsync();
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.progress).toBe(0);
      expect(state.value.error).toBe(error);
    });

    it('should respect minimum display time', async () => {
      const context = 'test-context';
      
      // Mock Date.now to control timing
      let currentTime = 1000;
      Date.now = vi.fn(() => currentTime);
      
      manager.startLoading(context, 'Loading...', { minDisplayTime: 500 });
      
      // Finish loading after only 100ms
      currentTime += 100;
      manager.finishLoading(context);
      
      // Should still be loading due to minimum display time
      let state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(true);
      
      // Wait for minimum display time to pass
      await vi.advanceTimersByTimeAsync(400);
      
      state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
    });

    it('should update global loading state', async () => {
      const context = 'test-context';
      
      manager.startLoading(context);
      expect(manager.isAnyLoading()).toBe(true);
      
      manager.finishLoading(context);
      await vi.runAllTimersAsync();
      
      expect(manager.isAnyLoading()).toBe(false);
      expect(manager.getGlobalLoadingState().value).toBe(false);
    });
  });

  describe('setError', () => {
    it('should set error state', () => {
      const context = 'test-context';
      const error = new Error('Test error');
      
      manager.startLoading(context);
      manager.setError(context, error);
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.error).toBe(error);
      expect(state.value.progress).toBe(0);
    });

    it('should update global loading state when setting error', () => {
      const context = 'test-context';
      const error = new Error('Test error');
      
      manager.startLoading(context);
      expect(manager.isAnyLoading()).toBe(true);
      
      manager.setError(context, error);
      expect(manager.isAnyLoading()).toBe(false);
    });
  });

  describe('getSkeletonConfig', () => {
    it('should return skeleton config for loading context', () => {
      const context = 'test-context';
      
      manager.startLoading(context, 'Loading...', {
        showSkeleton: true,
        skeletonType: 'table'
      });
      
      const config = manager.getSkeletonConfig(context);
      expect(config.type).toBe('table');
      expect(config.columns).toBe(6);
      expect(config.rows).toBe(5);
    });

    it('should return default config for non-loading context', () => {
      const context = 'test-context';
      
      const config = manager.getSkeletonConfig(context);
      expect(config.type).toBe('card');
    });

    it('should return appropriate config for different skeleton types', () => {
      const contexts = ['list', 'chart', 'card'];
      const types: Array<'list' | 'chart' | 'card'> = ['list', 'chart', 'card'];
      
      contexts.forEach((context, index) => {
        manager.startLoading(context, 'Loading...', {
          showSkeleton: true,
          skeletonType: types[index]
        });
        
        const config = manager.getSkeletonConfig(context);
        expect(config.type).toBe(types[index]);
      });
    });
  });

  describe('getProgressConfig', () => {
    it('should return progress config for loading context', () => {
      const context = 'test-context';
      
      manager.startLoading(context, 'Loading test...', {
        showProgress: true,
        progressType: 'circular'
      });
      
      manager.updateProgress(context, 75);
      
      const config = manager.getProgressConfig(context);
      expect(config.show).toBe(true);
      expect(config.type).toBe('circular');
      expect(config.progress).toBe(75);
      expect(config.message).toBe('Loading test...');
    });

    it('should not show progress when disabled', () => {
      const context = 'test-context';
      
      manager.startLoading(context, 'Loading...', {
        showProgress: false
      });
      
      const config = manager.getProgressConfig(context);
      expect(config.show).toBe(false);
    });
  });

  describe('withLoading', () => {
    it('should wrap async operation with loading state', async () => {
      const context = 'test-operation';
      const operation = vi.fn().mockResolvedValue('success');
      
      const result = await manager.withLoading(context, operation, 'Processing...');
      
      expect(operation).toHaveBeenCalledOnce();
      expect(result).toBe('success');
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.error).toBeNull();
    });

    it('should handle operation failures', async () => {
      const context = 'test-operation';
      const error = new Error('Operation failed');
      const operation = vi.fn().mockRejectedValue(error);
      
      await expect(manager.withLoading(context, operation)).rejects.toThrow('Operation failed');
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.error).toBe(error);
    });
  });

  describe('withProgressLoading', () => {
    it('should wrap async operation with progress updates', async () => {
      const context = 'test-operation';
      const operation = vi.fn().mockImplementation(async (updateProgress) => {
        updateProgress(25, 'Step 1');
        updateProgress(50, 'Step 2');
        updateProgress(75, 'Step 3');
        return 'success';
      });
      
      const result = await manager.withProgressLoading(context, operation, 'Starting...');
      
      expect(operation).toHaveBeenCalledOnce();
      expect(result).toBe('success');
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
      expect(state.value.progress).toBe(100);
    });
  });

  describe('batchLoading', () => {
    it('should handle multiple loading operations', async () => {
      const operations = [
        {
          context: 'op1',
          operation: () => Promise.resolve('result1'),
          message: 'Loading 1...'
        },
        {
          context: 'op2',
          operation: () => Promise.resolve('result2'),
          message: 'Loading 2...'
        }
      ];
      
      const results = await manager.batchLoading(operations);
      
      expect(results).toEqual(['result1', 'result2']);
      
      // All operations should be completed
      operations.forEach(({ context }) => {
        const state = manager.getLoadingStateReactive(context);
        expect(state.value.isLoading).toBe(false);
      });
    });
  });

  describe('getStats', () => {
    it('should return accurate statistics', () => {
      manager.startLoading('context1');
      manager.startLoading('context2');
      manager.finishLoading('context1');
      
      const stats = manager.getStats();
      
      expect(stats.activeLoadings).toBe(1);
      expect(stats.totalStates).toBe(2);
      expect(typeof stats.averageDuration).toBe('number');
      expect(typeof stats.longestLoading).toBe('number');
    });
  });

  describe('clearAll', () => {
    it('should clear all loading states', () => {
      manager.startLoading('context1');
      manager.startLoading('context2');
      
      expect(manager.isAnyLoading()).toBe(true);
      
      manager.clearAll();
      
      expect(manager.isAnyLoading()).toBe(false);
      expect(manager.getStats().totalStates).toBe(0);
    });
  });

  describe('integration with real application', () => {
    it('should work with actual async operations', async () => {
      const context = 'api-call';
      
      const apiCall = () => new Promise(resolve => {
        setTimeout(() => resolve({ data: 'test' }), 100);
      });
      
      const result = await manager.withLoading(context, apiCall, 'Fetching data...');
      
      expect(result).toEqual({ data: 'test' });
      
      const state = manager.getLoadingStateReactive(context);
      expect(state.value.isLoading).toBe(false);
    });

    it('should handle concurrent loading operations', async () => {
      const contexts = ['load1', 'load2', 'load3'];
      
      const promises = contexts.map(context => 
        manager.withLoading(context, () => 
          new Promise(resolve => setTimeout(() => resolve(context), 50))
        )
      );
      
      const results = await Promise.all(promises);
      
      expect(results).toEqual(contexts);
      expect(manager.isAnyLoading()).toBe(false);
    });

    it('should maintain performance under high load', async () => {
      const operationCount = 100;
      const operations = Array.from({ length: operationCount }, (_, i) => ({
        context: `operation-${i}`,
        operation: () => Promise.resolve(i),
        message: `Loading ${i}...`
      }));
      
      const startTime = performance.now();
      const results = await manager.batchLoading(operations);
      const endTime = performance.now();
      
      expect(results).toHaveLength(operationCount);
      expect(endTime - startTime).toBeLessThan(1000); // Should complete quickly
      expect(manager.isAnyLoading()).toBe(false);
    });
  });

  describe('performance requirements', () => {
    it('should meet loading state update performance targets', () => {
      const context = 'performance-test';
      
      const startTime = performance.now();
      
      // Perform many rapid updates
      manager.startLoading(context);
      for (let i = 0; i <= 100; i += 10) {
        manager.updateProgress(context, i);
      }
      manager.finishLoading(context);
      
      const endTime = performance.now();
      
      // Should complete updates quickly
      expect(endTime - startTime).toBeLessThan(50); // < 50ms
    });

    it('should handle memory efficiently with many contexts', () => {
      const contextCount = 1000;
      
      // Create many loading contexts
      for (let i = 0; i < contextCount; i++) {
        manager.startLoading(`context-${i}`, `Loading ${i}...`);
        manager.finishLoading(`context-${i}`);
      }
      
      const stats = manager.getStats();
      expect(stats.totalStates).toBe(contextCount);
      
      // Memory usage should be reasonable
      // This is a basic check - in a real app you'd monitor actual memory usage
      expect(stats.totalStates).toBeLessThan(contextCount * 2);
    });
  });
});