import React from 'react';
import PropTypes from 'prop-types';
import ConversionTrendChart from '../TestStatsDashboard/ConversionTrendChart'; // Reusing existing chart
import { __ } from '@wordpress/i18n';

// Sub-component for the results table
const ResultsSummaryTable = ({ aggregatedStats, variations, winnerVariantId }) => {
  if (!aggregatedStats || aggregatedStats.length === 0) {
    return <p>{__('No aggregated statistics available yet.', 'brickslift-ab-testing')}</p>;
  }

  const getVariantName = (variantId) => {
    const variation = variations.find(v => v.id === variantId || v.variant_id === variantId);
    return variation ? variation.name : `Variant ${variantId}`;
  };

  return (
    <table className="wp-list-table widefat striped summary-stats-table">
      <thead>
        <tr>
          <th>{__('Variation Name', 'brickslift-ab-testing')}</th>
          <th>{__('Views/Impressions', 'brickslift-ab-testing')}</th>
          <th>{__('Conversions', 'brickslift-ab-testing')}</th>
          <th>{__('Conversion Rate (%)', 'brickslift-ab-testing')}</th>
          {/* Optional: Add Lift and Confidence later */}
        </tr>
      </thead>
      <tbody>
        {aggregatedStats.map((stat) => {
          const views = parseInt(stat.total_views, 10) || 0;
          const conversions = parseInt(stat.total_conversions, 10) || 0;
          const conversionRate = views > 0 ? ((conversions / views) * 100).toFixed(2) : '0.00';
          const isWinner = stat.variant_id === winnerVariantId;
          return (
            <tr key={stat.variant_id} className={isWinner ? 'winner-row' : ''}>
              <td>
                {getVariantName(stat.variant_id)}
                {isWinner && <span className="winner-badge"> {__('(Winner)', 'brickslift-ab-testing')}</span>}
              </td>
              <td>{views}</td>
              <td>{conversions}</td>
              <td>{conversionRate}%</td>
            </tr>
          );
        })}
      </tbody>
    </table>
  );
};

ResultsSummaryTable.propTypes = {
  aggregatedStats: PropTypes.arrayOf(PropTypes.shape({
    variant_id: PropTypes.string.isRequired,
    total_views: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    total_conversions: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  })),
  variations: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.string,
    variant_id: PropTypes.string,
    name: PropTypes.string,
  })).isRequired,
  winnerVariantId: PropTypes.string,
};


const TestPerformanceResultsPanel = ({ aggregatedStats, dailyStats, variations, winnerVariantId }) => {
  // Transform dailyStats for the ConversionTrendChart
  // API dailyStats: [{ stat_date: "YYYY-MM-DD", variant_id: "control", impressions_count: "150", conversions_count: "10" }, ...]
  // Chart expects: [{ date: 'YYYY-MM-DD', variants: [{ variant_id: 'A', impressions: 100, conversions: 5 }, ...] }, ...]
  const transformDailyStatsForChart = (rawDailyStats) => {
    if (!rawDailyStats || rawDailyStats.length === 0) return [];

    const groupedByDate = rawDailyStats.reduce((acc, curr) => {
      const date = curr.stat_date;
      if (!acc[date]) {
        acc[date] = { date, variants: [] };
      }
      acc[date].variants.push({
        variant_id: curr.variant_id,
        impressions: parseInt(curr.impressions_count, 10) || 0,
        conversions: parseInt(curr.conversions_count, 10) || 0,
      });
      return acc;
    }, {});
    return Object.values(groupedByDate);
  };

  // Transform variations for the ConversionTrendChart's variantTotals prop
  // Chart expects: { variant_id_A: { name: 'Variant A' }, ... }
  const transformVariationsForChart = (testVariations) => {
    if (!testVariations || testVariations.length === 0) return {};
    return testVariations.reduce((acc, v) => {
      // Ensure we use the correct ID field, prefer 'id' if available, else 'variant_id'
      const id = v.id || v.variant_id;
      if (id) {
         acc[id] = { name: v.name || `Variant ${id}` };
      }
      return acc;
    }, {});
  };

  const chartDailyStats = transformDailyStatsForChart(dailyStats);
  const chartVariantTotals = transformVariationsForChart(variations);

  return (
    <div className="blft-panel test-performance-results-panel">
      <h2>{__('Performance Results', 'brickslift-ab-testing')}</h2>
      <ResultsSummaryTable
        aggregatedStats={aggregatedStats}
        variations={variations}
        winnerVariantId={winnerVariantId}
      />
      <ConversionTrendChart
        dailyStats={chartDailyStats}
        variantTotals={chartVariantTotals}
      />
    </div>
  );
};

TestPerformanceResultsPanel.propTypes = {
  aggregatedStats: PropTypes.arrayOf(PropTypes.object), // More specific shape in ResultsSummaryTable
  dailyStats: PropTypes.arrayOf(PropTypes.shape({
    stat_date: PropTypes.string.isRequired,
    variant_id: PropTypes.string.isRequired,
    impressions_count: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    conversions_count: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  })),
  variations: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.string, // This is the primary ID from the CPT
    variant_id: PropTypes.string, // This might be a slug-like or generated ID used in stats
    name: PropTypes.string,
    // other variation properties
  })).isRequired,
  winnerVariantId: PropTypes.string,
};

export default TestPerformanceResultsPanel;