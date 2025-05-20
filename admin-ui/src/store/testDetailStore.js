import { create } from 'zustand';
import { fetchTestData, fetchAggregatedStats, fetchDailyStats } from '../utils/api'; // Assuming api utils will be created/updated

const useTestDetailStore = create((set, get) => ({
  testData: null,
  aggregatedStats: null,
  dailyStats: null,
  isLoadingTestData: false,
  isLoadingAggregatedStats: false,
  isLoadingDailyStats: false,
  error: null,

  fetchTestDetails: async (testId) => {
    set({ isLoadingTestData: true, error: null });
    try {
      const data = await fetchTestData(testId);
      set({ testData: data, isLoadingTestData: false });
    } catch (error) {
      console.error('Error fetching test details:', error);
      set({ error: 'Failed to fetch test details.', isLoadingTestData: false });
    }
  },

  fetchTestAggregatedStats: async (testId) => {
    set({ isLoadingAggregatedStats: true, error: null });
    try {
      const stats = await fetchAggregatedStats(testId);
      set({ aggregatedStats: stats, isLoadingAggregatedStats: false });
    } catch (error) {
      console.error('Error fetching aggregated stats:', error);
      set({ error: 'Failed to fetch aggregated stats.', isLoadingAggregatedStats: false });
    }
  },

  fetchTestDailyStats: async (testId) => {
    set({ isLoadingDailyStats: true, error: null });
    try {
      const stats = await fetchDailyStats(testId);
      set({ dailyStats: stats, isLoadingDailyStats: false });
    } catch (error) {
      console.error('Error fetching daily stats:', error);
      set({ error: 'Failed to fetch daily stats.', isLoadingDailyStats: false });
    }
  },

  resetStore: () => {
    set({
      testData: null,
      aggregatedStats: null,
      dailyStats: null,
      isLoadingTestData: false,
      isLoadingAggregatedStats: false,
      isLoadingDailyStats: false,
      error: null,
    });
  },
}));

export default useTestDetailStore;