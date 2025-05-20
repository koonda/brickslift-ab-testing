import React, { useEffect } from 'react';
// import { useParams, useNavigate } from 'react-router-dom'; // Commented out as not fully used yet
// We might still need useNavigate for other purposes, but not for core testId param or edit navigation here.
// If other navigation is needed, it can be re-imported. For now, App.js handles major navigation.
import useTestDetailStore from '../../store/testDetailStore';
import TestOverviewPanel from './TestOverviewPanel';
import TestConfigurationPanel from './TestConfigurationPanel';
import TestPerformanceResultsPanel from './TestPerformanceResultsPanel';
import TestManagementControls from './TestManagementControls'; // Added import

// Accept testId and onNavigateToEdit as props from App.js's state-based routing
const TestDetailContainer = ({ testId: propTestId, onNavigateToEdit }) => {
  // const { testId: testIdFromParams } = useParams(); // Keep for potential future router integration
  // const navigate = useNavigate(); // Keep for potential future router integration
  const {
    testData,
    aggregatedStats,
    dailyStats,
    isLoadingTestData,
    isLoadingAggregatedStats,
    isLoadingDailyStats,
    error,
    fetchTestDetails,
    fetchTestAggregatedStats,
    fetchTestDailyStats,
    resetStore,
  } = useTestDetailStore();

  // Use the testId passed as a prop
  const testId = propTestId;

  useEffect(() => {
    if (testId) {
      fetchTestDetails(testId);
      fetchTestAggregatedStats(testId);
      fetchTestDailyStats(testId);
    }
    // Reset store on component unmount or when testId changes to ensure clean state
    return () => {
      resetStore();
    };
  }, [testId, fetchTestDetails, fetchTestAggregatedStats, fetchTestDailyStats, resetStore]);

  if (!testId) {
    // This case should ideally be handled by the App.js routing logic
    // but as a fallback:
    return <div className="notice notice-warning"><p>No Test ID provided to TestDetailContainer.</p></div>;
  }

  if (isLoadingTestData || isLoadingAggregatedStats || isLoadingDailyStats) {
    return <div>Loading test details...</div>;
  }

  if (error) {
    return <div className="notice notice-error"><p>{error}</p></div>;
  }

  if (!testData) {
    return <div>No test data found.</div>;
  }

  const handleEditTest = () => {
    // Use the onNavigateToEdit prop passed from App.js
    if (onNavigateToEdit) {
      onNavigateToEdit(testId);
    } else {
      // Fallback or error if the prop isn't passed, though App.js should provide it.
      console.error("onNavigateToEdit handler not provided to TestDetailContainer");
      // As a less ideal fallback, could use navigate if react-router was fully set up for this path.
      // navigate(`/tests/edit/${testId}`); // This would require react-router to handle this path
    }
  };

  return (
    <div className="wrap blft-test-detail-container">
      {/* The "Back to Test List" button is now handled by App.js */}
      {/* Title is also handled by App.js */}
      <TestOverviewPanel testData={testData} />
      <TestConfigurationPanel testData={testData} onEditTest={handleEditTest} />
      <TestPerformanceResultsPanel
        aggregatedStats={aggregatedStats}
        dailyStats={dailyStats}
        variations={testData.variations || []}
        winnerVariantId={testData.winner_variant_id || testData._blft_winner_variant_id} // Check both potential winner fields
      />
      <TestManagementControls
        test={testData}
        testDetailStore={{ fetchTestDetails }}
        // testsStore prop is available in TestManagementControls,
        // but TestDetailContainer doesn't have direct access to testsStore.
        // The control component has a mock for it for now.
      />
      {/*
        Optional: Raw data for debugging, can be removed later
        <pre>Test Data: {JSON.stringify(testData, null, 2)}</pre>
        <pre>Aggregated Stats: {JSON.stringify(aggregatedStats, null, 2)}</pre>
        <pre>Daily Stats: {JSON.stringify(dailyStats, null, 2)}</pre>
      */}
    </div>
  );
};

export default TestDetailContainer;