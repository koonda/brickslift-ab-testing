import { __ } from '@wordpress/i18n';
import { LineChart } from '@wordpress/charts';
import { Card, CardBody, CardHeader } from '@wordpress/components';

const ConversionTrendChart = ({ dailyStats, variantTotals }) => {
	if (!dailyStats || dailyStats.length === 0) {
		return <p>{__('Not enough data to display conversion trends.', 'brickslift-ab-testing')}</p>;
	}

	const variantNamesMap = Object.entries(variantTotals || {}).reduce((acc, [id, data]) => {
		acc[id] = data.name || id;
		return acc;
	}, {});

	// Prepare data for the chart
	// The chart expects data in a specific format.
	// Example: series = [ { name: 'Variant A', data: [ { x: '2023-01-01', y: 2.5 }, ... ] }, ... ]
	const series = [];
	const uniqueVariantIds = [...new Set(dailyStats.flatMap(day => day.variants.map(v => v.variant_id)))];

	uniqueVariantIds.forEach(variantId => {
		const variantDataPoints = [];
		dailyStats.forEach(dailyEntry => {
			const variantStat = dailyEntry.variants.find(v => v.variant_id === variantId);
			if (variantStat) {
				const cr = variantStat.impressions > 0 ? (variantStat.conversions / variantStat.impressions) * 100 : 0;
				variantDataPoints.push({
					x: dailyEntry.date, // Assuming date is a string like 'YYYY-MM-DD'
					y: parseFloat(cr.toFixed(2))
				});
			} else {
				// If a variant has no data for a specific day, add a zero point or handle as needed
				variantDataPoints.push({
					x: dailyEntry.date,
					y: 0
				});
			}
		});
		series.push({
			name: variantNamesMap[variantId] || variantId,
			data: variantDataPoints.sort((a, b) => new Date(a.x) - new Date(b.x)) // Sort by date
		});
	});

	if (series.length === 0 || series.every(s => s.data.length === 0)) {
		return <p>{__('No data points available for the trend chart.', 'brickslift-ab-testing')}</p>;
	}
	
	const chartOptions = {
		// TODO: Explore more options from @wordpress/charts for better tooltips, legends, axes formatting.
		// For example, formatting y-axis as percentage.
		// xFormat: '%Y-%m-%d', // If dates need specific parsing/formatting for the axis
		// yFormat: '%.2f%%', // This might not be directly supported, may need custom tick formatting
		isSmooth: true,
		legend: {
			isVisible: true,
			position: 'bottom', // 'top', 'bottom', 'left', 'right'
		},
		tooltip: {
			// Custom tooltip formatter if needed
			// format: (value, name,  { x, y }) => `${name}: ${y.toFixed(2)}% on ${x}`,
		},
		// height: 300, // Optional: set a fixed height
	};

	return (
		<Card>
			<CardHeader>
				<strong>{__('Conversion Rate Trend (%)', 'brickslift-ab-testing')}</strong>
			</CardHeader>
			<CardBody>
				<div style={{ minHeight: '300px' }}> {/* Ensure chart has space to render */}
					<LineChart data={series} options={chartOptions} />
				</div>
			</CardBody>
		</Card>
	);
};

export default ConversionTrendChart;