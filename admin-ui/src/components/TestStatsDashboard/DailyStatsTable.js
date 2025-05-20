import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

const DailyStatsTable = ({ dailyStats, variantTotals }) => {
	if (!dailyStats || dailyStats.length === 0) {
		return <p>{__('No daily statistics available to display.', 'brickslift-ab-testing')}</p>;
	}

	// Create a list of all known variant IDs and their names from variantTotals for consistent column ordering
	const allVariantIds = Object.keys(variantTotals || {});
	const variantNamesMap = allVariantIds.reduce((acc, id) => {
		acc[id] = variantTotals[id]?.name || id;
		return acc;
	}, {});


	return (
		<table className="wp-list-table widefat fixed striped blft-daily-stats-table">
			<thead>
				<tr>
					<th scope="col">{__('Date', 'brickslift-ab-testing')}</th>
					{allVariantIds.map(variantId => (
						<Fragment key={variantId}>
							<th scope="col" colSpan="3" style={{ textAlign: 'center' }}>
								{variantNamesMap[variantId]}
							</th>
						</Fragment>
					))}
				</tr>
				<tr>
					<th scope="col"></th> {/* Empty for Date column */}
					{allVariantIds.map(variantId => (
						<Fragment key={`${variantId}-sub`}>
							<th scope="col" style={{ textAlign: 'center' }}>{__('Impr.', 'brickslift-ab-testing')}</th>
							<th scope="col" style={{ textAlign: 'center' }}>{__('Conv.', 'brickslift-ab-testing')}</th>
							<th scope="col" style={{ textAlign: 'center' }}>{__('CR (%)', 'brickslift-ab-testing')}</th>
						</Fragment>
					))}
				</tr>
			</thead>
			<tbody>
				{dailyStats.map((dailyEntry) => (
					<tr key={dailyEntry.date}>
						<td>{dailyEntry.date}</td>
						{allVariantIds.map(variantId => {
							const variantData = dailyEntry.variants.find(v => v.variant_id === variantId);
							const impressions = variantData ? variantData.impressions : 0;
							const conversions = variantData ? variantData.conversions : 0;
							const cr = impressions > 0 ? ((conversions / impressions) * 100).toFixed(2) : '0.00';
							return (
								<Fragment key={`${dailyEntry.date}-${variantId}`}>
									<td style={{ textAlign: 'center' }}>{impressions}</td>
									<td style={{ textAlign: 'center' }}>{conversions}</td>
									<td style={{ textAlign: 'center' }}>{cr}%</td>
								</Fragment>
							);
						})}
					</tr>
				))}
			</tbody>
		</table>
	);
};

export default DailyStatsTable;