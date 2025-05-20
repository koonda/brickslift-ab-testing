/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n'; // Added sprintf
import { useState, useEffect, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Panel, PanelBody, PanelRow, Spinner, Notice } from '@wordpress/components';

// Placeholder for sub-components that will be created later
import KeyMetricsDisplay from './KeyMetricsDisplay';
import ConversionTrendChart from './ConversionTrendChart'; // Uncommented
import DailyStatsTable from './DailyStatsTable';
import WinnerHighlight from './WinnerHighlight';
import TestInformation from './TestInformation';

const TestStatsDashboard = ({ testId }) => {
	const [testDetails, setTestDetails] = useState(null);
	const [dailyStats, setDailyStats] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [error, setError] = useState(null);

	useEffect(() => {
		if (!testId) {
			setError(__('No Test ID provided.', 'brickslift-ab-testing'));
			setIsLoading(false);
			return;
		}

		const fetchTestData = async () => {
			setIsLoading(true);
			setError(null);
			try {
				const details = await apiFetch({ path: `/blft/v1/tests/${testId}` });
				setTestDetails(details);

				// Fetch daily stats - adjust date range as needed, or fetch all time for now
				const stats = await apiFetch({ path: `/blft/v1/test-stats-daily/${testId}` });
				setDailyStats(stats);

			} catch (fetchError) {
				console.error('Error fetching test statistics:', fetchError);
				setError(fetchError.message || __('Failed to load test statistics.', 'brickslift-ab-testing'));
			} finally {
				setIsLoading(false);
			}
		};

		fetchTestData();
	}, [testId]);

	if (isLoading) {
		return (
			<div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '200px' }}>
				<Spinner />
				<p style={{ marginLeft: '8px' }}>{__('Loading statistics...', 'brickslift-ab-testing')}</p>
			</div>
		);
	}

	if (error) {
		return (
			<Notice status="error" isDismissible={false}>
				<p>{error}</p>
			</Notice>
		);
	}

	if (!testDetails) {
		return <p>{__('Test details not found.', 'brickslift-ab-testing')}</p>;
	}

	// Calculate overall metrics (simplified for now)
	let overallImpressions = 0;
	let overallConversions = 0;
	const variantTotals = {};

	dailyStats.forEach(dailyEntry => {
		dailyEntry.variants.forEach(variantStat => {
			if (!variantTotals[variantStat.variant_id]) {
				variantTotals[variantStat.variant_id] = {
					name: variantStat.variant_name,
					impressions: 0,
					conversions: 0,
				};
			}
			variantTotals[variantStat.variant_id].impressions += variantStat.impressions;
			variantTotals[variantStat.variant_id].conversions += variantStat.conversions;
			overallImpressions += variantStat.impressions;
			overallConversions += variantStat.conversions;
		});
	});

	return (
		<div className="blft-test-stats-dashboard">
			<h1>
				{sprintf(
					/* translators: %s: Test Title */
					__('Statistics for: %s', 'brickslift-ab-testing'),
					testDetails.title?.rendered || testDetails.title?.raw || __('N/A', 'brickslift-ab-testing')
				)}
			</h1>

			<Panel>
				<PanelBody title={__('Test Information', 'brickslift-ab-testing')} initialOpen={true}>
					<TestInformation testDetails={testDetails} />
				</PanelBody>
			</Panel>

			<Panel>
				<PanelBody title={__('Overall Performance', 'brickslift-ab-testing')} initialOpen={true}>
					<KeyMetricsDisplay variantTotals={variantTotals} />
					<WinnerHighlight variantTotals={variantTotals} />
				</PanelBody>
			</Panel>
			
			<Panel>
				<PanelBody title={__('Daily Breakdown', 'brickslift-ab-testing')} initialOpen={true}>
					<ConversionTrendChart dailyStats={dailyStats} variantTotals={variantTotals} />
					<DailyStatsTable dailyStats={dailyStats} variantTotals={variantTotals} />
					{/* Raw data display can be kept for debugging or removed later */}
					{ dailyStats.length > 0 && (
						<details style={{marginTop: '20px'}}>
							<summary>{__('View Raw Daily Data (JSON)', 'brickslift-ab-testing')}</summary>
							<pre>{JSON.stringify(dailyStats, null, 2)}</pre>
						</details>
					)}
				</PanelBody>
			</Panel>

		</div>
	);
};

export default TestStatsDashboard;