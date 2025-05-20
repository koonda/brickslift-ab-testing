import { __, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

const MIN_IMPRESSIONS_FOR_SIGNIFICANCE = 100; // Example threshold

const WinnerHighlight = ({ variantTotals }) => {
	if (!variantTotals || Object.keys(variantTotals).length === 0) {
		return null; // No data to determine a winner
	}

	const variantsArray = Object.entries(variantTotals).map(([id, data]) => ({
		id,
		name: data.name || id,
		impressions: data.impressions,
		conversions: data.conversions,
		cr: data.impressions > 0 ? (data.conversions / data.impressions) * 100 : 0,
	}));

	if (variantsArray.length === 0) {
		return null;
	}

	// Find the winner (highest CR)
	let winner = variantsArray.reduce((prev, current) => (prev.cr > current.cr) ? prev : current);

	// Check for ties in CR
	const highestCr = winner.cr;
	const tiedWinners = variantsArray.filter(variant => variant.cr === highestCr);

	let lowDataWarning = false;
	// Check if any variant (especially the winner or tied winners) has low impressions
	tiedWinners.forEach(v => {
		if (v.impressions < MIN_IMPRESSIONS_FOR_SIGNIFICANCE) {
			lowDataWarning = true;
		}
	});
	// Also consider if all variants have low impressions
	if (!lowDataWarning && variantsArray.every(v => v.impressions < MIN_IMPRESSIONS_FOR_SIGNIFICANCE)) {
		lowDataWarning = true;
	}


	let message;
	if (highestCr === 0 && variantsArray.every(v => v.impressions === 0 || v.conversions === 0)) {
		message = __('No conversions recorded yet, or all variants have 0% CR.', 'brickslift-ab-testing');
	} else if (tiedWinners.length > 1) {
		const winnerNames = tiedWinners.map(v => v.name).join(', ');
		message = sprintf(
			/* translators: %1$s: Winner names, %2$s: Conversion Rate */
			__('Multiple variants (%1$s) are currently tied for the highest Conversion Rate: %2$s%%.', 'brickslift-ab-testing'),
			winnerNames,
			highestCr.toFixed(2)
		);
	} else {
		message = sprintf(
			/* translators: %1$s: Winner name, %2$s: Conversion Rate */
			__('Current Leader: "%1$s" with a Conversion Rate of %2$s%%.', 'brickslift-ab-testing'),
			winner.name,
			highestCr.toFixed(2)
		);
	}

	return (
		<div className="blft-winner-highlight" style={{ marginTop: '15px', marginBottom: '15px' }}>
			<Notice status="info" isDismissible={false}>
				<p>{message}</p>
				{lowDataWarning && (
					<p style={{ marginTop: '8px', fontStyle: 'italic' }}>
						{sprintf(
							/* translators: %d: Minimum impressions threshold */
							__('Note: Data volume is low (some variants have less than %d impressions). Statistical significance may be limited.', 'brickslift-ab-testing'),
							MIN_IMPRESSIONS_FOR_SIGNIFICANCE
						)}
					</p>
				)}
			</Notice>
		</div>
	);
};

export default WinnerHighlight;