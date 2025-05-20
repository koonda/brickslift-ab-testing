import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { sprintf } from '@wordpress/i18n';
import { __ } from '@wordpress/i18n';
import './StatisticsView.scss';

const StatisticsView = ({ testId, testStatus }) => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [period, setPeriod] = useState(testStatus === 'completed' ? 'all_time' : '7days');
  const [winningVariant, setWinningVariant] = useState(null);
  const [recommendationMessage, setRecommendationMessage] = useState('');

  const isCompletedTest = testStatus === 'completed';

  const periodOptions = [
    { value: '7days', label: __('Last 7 Days', 'brickslift-ab-testing') },
    { value: '30days', label: __('Last 30 Days', 'brickslift-ab-testing') },
    { value: 'current_month', label: __('Current Month', 'brickslift-ab-testing') },
    { value: 'all_time', label: __('All Time', 'brickslift-ab-testing') },
  ];

  const calculateConversionRateValue = (conversions, views) => {
    if (views === 0) {
      return 0; // Return 0 for calculation purposes if views are zero
    }
    return (conversions / views) * 100;
  };

  const fetchStats = useCallback(async (currentPeriod) => {
    if (!testId) {
      setStats(null);
      setLoading(false);
      setWinningVariant(null);
      setRecommendationMessage('');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const fetchPeriod = isCompletedTest ? 'all_time' : currentPeriod;
      const response = await apiFetch({
        path: `/blft/v1/stats?test_id=${testId}&period=${fetchPeriod}`,
      });
      setStats(response);

      if (isCompletedTest && response && response.length > 0) {
        let winner = null;
        let maxConversionRate = -1;

        response.forEach(variant => {
          const rate = calculateConversionRateValue(variant.conversions, variant.views);
          if (rate > maxConversionRate) {
            maxConversionRate = rate;
            winner = variant;
          } else if (rate === maxConversionRate && winner) {
            // If rates are equal, there's no clear single winner based on rate alone
            winner = null; // Or handle tie differently, e.g. pick one with more views/conversions
          }
        });

        if (winner && maxConversionRate > 0) {
          setWinningVariant(winner);
          setRecommendationMessage(
            sprintf(
              /* translators: %s: Winning variant name or ID. */
              __("Recommendation: Variant '%s' performed best. Consider manually implementing its changes.", 'brickslift-ab-testing'),
              winner.variant_name || winner.variant_id
            )
          );
        } else if (maxConversionRate === 0 && response.some(v => v.views > 0)) {
            setWinningVariant(null);
            setRecommendationMessage(__('All variants have a 0% conversion rate. Review the results to determine next steps.', 'brickslift-ab-testing'));
        } else if (response.every(v => v.views === 0)) {
            setWinningVariant(null);
            setRecommendationMessage(__('No views recorded for any variant. Unable to determine a winner.', 'brickslift-ab-testing'));
        }
         else {
          setWinningVariant(null);
          setRecommendationMessage(__('Results inconclusive or no clear winner. Review the results to determine next steps.', 'brickslift-ab-testing'));
        }
      } else if (isCompletedTest) {
        setWinningVariant(null);
        setRecommendationMessage(__('No statistics data available to determine a winner for this completed test.', 'brickslift-ab-testing'));
      } else {
        setWinningVariant(null);
        setRecommendationMessage('');
      }

    } catch (err) {
      setError(err.message || __('Failed to fetch statistics.', 'brickslift-ab-testing'));
      setStats(null);
      setWinningVariant(null);
      setRecommendationMessage('');
    } finally {
      setLoading(false);
    }
  }, [testId, isCompletedTest]); // Removed period from dependencies, will pass it directly

  useEffect(() => {
    fetchStats(period);
  }, [fetchStats, period]); // Add period here so it refetches when period changes (for non-completed tests)

  const handlePeriodChange = (event) => {
    if (!isCompletedTest) {
      setPeriod(event.target.value);
    }
  };

  const formatConversionRateDisplay = (conversions, views) => {
    if (views === 0) {
      return 'N/A';
    }
    const rate = calculateConversionRateValue(conversions, views);
    return `${rate.toFixed(2)}%`;
  };

  if (!testId) {
    return (
      <div className="blft-statistics-view">
        <p>{__('No test selected or test ID is missing.', 'brickslift-ab-testing')}</p>
      </div>
    );
  }

  return (
    <div className="blft-statistics-view">
      {isCompletedTest && (
        <div className="blft-test-completed-notice">
          <p>{__('This test has completed.', 'brickslift-ab-testing')}</p>
        </div>
      )}

      {!isCompletedTest && (
        <div className="blft-period-selector">
          <label htmlFor="blft-period-select">{__('Select Period:', 'brickslift-ab-testing')}</label>
          <select id="blft-period-select" value={period} onChange={handlePeriodChange} disabled={isCompletedTest}>
            {periodOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>
      )}

      {loading && <div className="blft-loading">{__('Loading statistics...', 'brickslift-ab-testing')}</div>}
      {error && <div className="blft-error">{__('Error:', 'brickslift-ab-testing')} {error}</div>}

      {!loading && !error && stats && stats.length > 0 && (
        <>
          <table className="blft-statistics-table">
            <thead>
              <tr>
                <th>{__('Variant Name/ID', 'brickslift-ab-testing')}</th>
                <th>{__('Total Views', 'brickslift-ab-testing')}</th>
                <th>{__('Total Conversions', 'brickslift-ab-testing')}</th>
                <th>{__('Conversion Rate', 'brickslift-ab-testing')}</th>
                {isCompletedTest && <th>{__('Status', 'brickslift-ab-testing')}</th>}
              </tr>
            </thead>
            <tbody>
              {stats.map((variantStat) => (
                <tr
                  key={variantStat.variant_id}
                  className={isCompletedTest && winningVariant && winningVariant.variant_id === variantStat.variant_id ? 'blft-winner-variant' : ''}
                >
                  <td>{variantStat.variant_name || variantStat.variant_id}</td>
                  <td>{variantStat.views}</td>
                  <td>{variantStat.conversions}</td>
                  <td>{formatConversionRateDisplay(variantStat.conversions, variantStat.views)}</td>
                  {isCompletedTest && (
                    <td>
                      {winningVariant && winningVariant.variant_id === variantStat.variant_id ? (
                        <span className="blft-winner-badge">{__('Winner', 'brickslift-ab-testing')}</span>
                      ) : ''}
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
          {isCompletedTest && recommendationMessage && (
            <div className="blft-recommendation-message">
              <p>{recommendationMessage}</p>
            </div>
          )}
        </>
      )}

      {!loading && !error && (!stats || stats.length === 0) && (
        <div className="blft-no-data">
          {isCompletedTest
            ? __('No statistics data found for this completed test.', 'brickslift-ab-testing')
            : __('No statistics available for the selected period.', 'brickslift-ab-testing')}
        </div>
      )}
    </div>
  );
};

export default StatisticsView;