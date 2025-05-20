import React from 'react';
import PropTypes from 'prop-types';

const TestConfigurationPanel = ({ testData, onEditTest }) => {
  if (!testData) {
    return <p>Loading configuration...</p>;
  }

  const {
    description,
    variations,
    goal_type: goalType,
    goal_link: goalLink, // For 'link_click'
    goal_selector: goalSelector, // For 'element_interaction'
    // Assuming these are the correct meta keys for goal parameters
    _blft_goal_event_category: goalEventCategory, // For 'custom_event'
    _blft_goal_event_action: goalEventAction, // For 'custom_event'
    _blft_goal_event_label: goalEventLabel, // For 'custom_event'
    _blft_goal_event_value: goalEventValue, // For 'custom_event'
    start_date: startDate,
    end_date: endDate,
    test_duration_days: durationDays,
    // Add other relevant configuration fields as needed
  } = testData;

  const getGoalTypeLabel = (type) => {
    const labels = {
      page_visit: 'Page Visit',
      link_click: 'Link Click',
      form_submission: 'Form Submission (Not directly trackable, relies on thank you page)',
      element_interaction: 'Element Interaction',
      custom_event: 'Custom Event',
      woocommerce_purchase: 'WooCommerce Purchase',
    };
    return labels[type] || type;
  };

  const renderGoalParameters = () => {
    switch (goalType) {
      case 'link_click':
        return <p><strong>Goal Link:</strong> {goalLink || 'N/A'}</p>;
      case 'element_interaction':
        return <p><strong>Goal Selector:</strong> {goalSelector || 'N/A'}</p>;
      case 'custom_event':
        return (
          <>
            <p><strong>Event Category:</strong> {goalEventCategory || 'N/A'}</p>
            <p><strong>Event Action:</strong> {goalEventAction || 'N/A'}</p>
            <p><strong>Event Label:</strong> {goalEventLabel || '(Optional)'}</p>
            <p><strong>Event Value:</strong> {goalEventValue || '(Optional)'}</p>
          </>
        );
      case 'page_visit':
        return <p><strong>Goal Page URL:</strong> (Implied by variation content/URL if not a separate field)</p>;
      default:
        return <p>No specific parameters for this goal type.</p>;
    }
  };

  const renderSchedule = () => {
    let scheduleInfo = 'Not scheduled.';
    if (startDate) {
      scheduleInfo = `Starts: ${new Date(startDate).toLocaleDateString()}`;
      if (endDate) {
        scheduleInfo += `, Ends: ${new Date(endDate).toLocaleDateString()}`;
      } else if (durationDays) {
        scheduleInfo += `, Runs for: ${durationDays} day(s)`;
      }
    } else if (durationDays) {
      scheduleInfo = `Runs for: ${durationDays} day(s) (start date not set)`;
    }
    return scheduleInfo;
  };


  return (
    <div className="blft-panel test-configuration-panel">
      <h2>Test Configuration</h2>
      <button onClick={onEditTest} className="button button-secondary" style={{ float: 'right', marginTop: '-30px' }}>
        Edit Test
      </button>
      <table className="form-table">
        <tbody>
          <tr>
            <th scope="row">Description</th>
            <td>{description || 'N/A'}</td>
          </tr>
          <tr>
            <th scope="row">Goal Type</th>
            <td>{getGoalTypeLabel(goalType)}</td>
          </tr>
          <tr>
            <th scope="row">Goal Parameters</th>
            <td>{renderGoalParameters()}</td>
          </tr>
          <tr>
            <th scope="row">Schedule</th>
            <td>{renderSchedule()}</td>
          </tr>
        </tbody>
      </table>

      <h3>Variations</h3>
      {variations && variations.length > 0 ? (
        <table className="wp-list-table widefat striped variations-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Distribution (%)</th>
              <th>Content/URL/Element ID</th>
            </tr>
          </thead>
          <tbody>
            {variations.map((variation, index) => (
              <tr key={variation.id || `var-${index}`}>
                <td>{variation.name || 'N/A'}</td>
                <td>{variation.distribution_weight || 'N/A'}</td>
                <td>
                  {variation.type === 'url_redirect' && (variation.redirect_url || 'N/A')}
                  {variation.type === 'element_content' && (variation.element_selector || 'N/A')}
                  {/* Add more types as needed */}
                  {!variation.type && (variation.content || variation.url || 'N/A')}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : (
        <p>No variations configured.</p>
      )}
    </div>
  );
};

TestConfigurationPanel.propTypes = {
  testData: PropTypes.shape({
    description: PropTypes.string,
    variations: PropTypes.arrayOf(PropTypes.shape({
      id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
      name: PropTypes.string,
      distribution_weight: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
      type: PropTypes.string, // e.g., 'url_redirect', 'element_content'
      redirect_url: PropTypes.string,
      element_selector: PropTypes.string,
      content: PropTypes.string, // Fallback or for other types
      url: PropTypes.string, // Fallback or for other types
    })),
    goal_type: PropTypes.string,
    goal_link: PropTypes.string,
    goal_selector: PropTypes.string,
    _blft_goal_event_category: PropTypes.string,
    _blft_goal_event_action: PropTypes.string,
    _blft_goal_event_label: PropTypes.string,
    _blft_goal_event_value: PropTypes.string,
    start_date: PropTypes.string,
    end_date: PropTypes.string,
    test_duration_days: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  }),
  onEditTest: PropTypes.func.isRequired,
};

export default TestConfigurationPanel;