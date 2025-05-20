import React from 'react';
import PropTypes from 'prop-types';

const TestOverviewPanel = ({ testData }) => {
  if (!testData) {
    return <p>Loading overview...</p>;
  }

  const {
    name,
    _blft_status: status,
    _blft_hypothesis: hypothesis,
    start_date: startDate,
    end_date: endDate,
    test_duration_days: durationDays,
    winner_variant_id: winnerVariantId,
    variations // Assuming variations array is part of testData and contains id and name
  } = testData;

  const getStatusLabel = (currentStatus) => {
    const statusMap = {
      draft: 'Draft',
      running: 'Running',
      paused: 'Paused',
      completed: 'Completed',
      archived: 'Archived',
    };
    return statusMap[currentStatus] || currentStatus;
  };

  const calculateDurationDisplay = () => {
    if (startDate && endDate) {
      const start = new Date(startDate);
      const end = new Date(endDate);
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      return `${diffDays} day(s) (from ${start.toLocaleDateString()} to ${end.toLocaleDateString()})`;
    }
    if (durationDays) {
      return `${durationDays} day(s)`;
    }
    return 'Not specified';
  };

  const getWinnerName = () => {
    if (!winnerVariantId || !variations || variations.length === 0) {
      return 'Not declared';
    }
    const winnerVariation = variations.find(v => v.id === winnerVariantId || v.variant_id === winnerVariantId); // Handle both possible ID fields
    return winnerVariation ? winnerVariation.name : `ID: ${winnerVariantId} (Name not found)`;
  };

  return (
    <div className="blft-panel test-overview-panel">
      <h2>Test Overview</h2>
      <table className="form-table">
        <tbody>
          <tr>
            <th scope="row">Test Name</th>
            <td>{name || 'N/A'}</td>
          </tr>
          <tr>
            <th scope="row">Status</th>
            <td>{getStatusLabel(status)}</td>
          </tr>
          <tr>
            <th scope="row">Hypothesis</th>
            <td>{hypothesis || 'N/A'}</td>
          </tr>
          <tr>
            <th scope="row">Duration</th>
            <td>{calculateDurationDisplay()}</td>
          </tr>
          <tr>
            <th scope="row">Winner</th>
            <td>{getWinnerName()}</td>
          </tr>
        </tbody>
      </table>
    </div>
  );
};

TestOverviewPanel.propTypes = {
  testData: PropTypes.shape({
    name: PropTypes.string,
    _blft_status: PropTypes.string,
    _blft_hypothesis: PropTypes.string,
    start_date: PropTypes.string,
    end_date: PropTypes.string,
    test_duration_days: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    winner_variant_id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    variations: PropTypes.arrayOf(PropTypes.shape({
        id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
        variant_id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
        name: PropTypes.string,
    }))
  }),
};

export default TestOverviewPanel;