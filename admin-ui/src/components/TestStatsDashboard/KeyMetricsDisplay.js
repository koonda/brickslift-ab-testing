import { __, sprintf } from '@wordpress/i18n';
import { Card, CardBody, CardHeader, Flex, FlexBlock, FlexItem } from '@wordpress/components';

const KeyMetricsDisplay = ({ variantTotals }) => {
	if (!variantTotals || Object.keys(variantTotals).length === 0) {
		return <p>{__('No variant data available to display key metrics.', 'brickslift-ab-testing')}</p>;
	}

	const variantsArray = Object.entries(variantTotals).map(([id, data]) => ({
		id,
		...data,
		cr: data.impressions > 0 ? (data.conversions / data.impressions) * 100 : 0,
	}));

	// Calculate average CR of all variants for lift calculation
	const totalCrSum = variantsArray.reduce((acc, variant) => acc + variant.cr, 0);
	const averageCrAll = variantsArray.length > 0 ? totalCrSum / variantsArray.length : 0;


	return (
		<div className="blft-key-metrics-display" style={{ display: 'flex', flexWrap: 'wrap', gap: '16px', marginBottom: '20px' }}>
			{variantsArray.map((variant) => {
				let lift = 0;
				let liftText = __('N/A (baseline or only variant)', 'brickslift-ab-testing');

				if (variantsArray.length > 1) {
					// Calculate average CR of *other* variants
					let otherVariantsCrSum = 0;
					let otherVariantsCount = 0;
					variantsArray.forEach(vOther => {
						if (vOther.id !== variant.id) {
							otherVariantsCrSum += vOther.cr;
							otherVariantsCount++;
						}
					});
					const averageCrOthers = otherVariantsCount > 0 ? otherVariantsCrSum / otherVariantsCount : 0;

					if (averageCrOthers > 0) {
						lift = ((variant.cr - averageCrOthers) / averageCrOthers) * 100;
						liftText = `${lift.toFixed(2)}%`;
					} else if (variant.cr > 0) {
						liftText = __('+∞% (others have 0% CR)', 'brickslift-ab-testing');
					} else {
						liftText = __('0% (all 0% CR)', 'brickslift-ab-testing');
					}
				}


				return (
					<Card key={variant.id} style={{ minWidth: '250px', flexGrow: 1 }}>
						<CardHeader>
							<strong>{variant.name || variant.id}</strong>
						</CardHeader>
						<CardBody>
							<Flex direction="column" gap="2">
								<FlexItem>
									<strong>{__('Impressions:', 'brickslift-ab-testing')}</strong> {variant.impressions}
								</FlexItem>
								<FlexItem>
									<strong>{__('Conversions:', 'brickslift-ab-testing')}</strong> {variant.conversions}
								</FlexItem>
								<FlexItem>
									<strong>{__('Conversion Rate:', 'brickslift-ab-testing')}</strong> {variant.cr.toFixed(2)}%
								</FlexItem>
								<FlexItem>
									<strong>{__('Lift vs Others Avg:', 'brickslift-ab-testing')}</strong>
									<span style={{ color: lift > 0 ? 'green' : (lift < 0 ? 'red' : 'inherit') }}>
										{' '}{liftText}
									</span>
								</FlexItem>
							</Flex>
						</CardBody>
					</Card>
				);
			})}
		</div>
	);
};

export default KeyMetricsDisplay;