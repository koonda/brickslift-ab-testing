import { __, sprintf } from '@wordpress/i18n';
import { PanelRow } from '@wordpress/components';
import { dateI18n, getSettings } from '@wordpress/date';

const TestInformation = ({ testDetails }) => {
	if (!testDetails) {
		return null;
	}

	const {
		blft_status,
		status, // WordPress post status
		goal_type,
		// Goal specific details - these are now top-level in testDetails from Tests_Endpoint
		goal_pv_url,
		goal_pv_url_match_type,
		goal_sc_element_selector,
		goal_fs_form_selector,
		// Add other goal-specific fields as needed from testDetails schema
		date_created,
		// We might need _blft_test_start_date and _blft_test_actual_end_date if they are populated
	} = testDetails;

	const formatDate = (dateString) => {
		if (!dateString) return __('N/A', 'brickslift-ab-testing');
		// Uses WordPress date settings for formatting
		return dateI18n(getSettings().formats.date, dateString);
	};

	let goalDescription = goal_type || __('N/A', 'brickslift-ab-testing');
	switch (goal_type) {
		case 'page_visit':
			goalDescription = sprintf(
				/* translators: %1$s: URL, %2$s: Match Type */
				__('Page Visit: %1$s (Match: %2$s)', 'brickslift-ab-testing'),
				goal_pv_url || 'N/A',
				goal_pv_url_match_type || 'N/A'
			);
			break;
		case 'selector_click':
			goalDescription = sprintf(
				/* translators: %s: CSS Selector */
				__('Selector Click: %s', 'brickslift-ab-testing'),
				goal_sc_element_selector || 'N/A'
			);
			break;
		case 'form_submission':
			goalDescription = sprintf(
				/* translators: %s: Form Selector */
				__('Form Submission: %s', 'brickslift-ab-testing'),
				goal_fs_form_selector || 'N/A'
			);
			break;
		// Add more cases for other goal types as defined in CPT_Manager/Tests_Endpoint
	}


	return (
		<div>
			<PanelRow>
				<strong>{__('Status:', 'brickslift-ab-testing')}</strong> {blft_status || status || __('N/A', 'brickslift-ab-testing')}
			</PanelRow>
			<PanelRow>
				<strong>{__('Conversion Goal:', 'brickslift-ab-testing')}</strong> {goalDescription}
			</PanelRow>
			<PanelRow>
				<strong>{__('Created Date:', 'brickslift-ab-testing')}</strong> {formatDate(date_created)}
			</PanelRow>
			{/*
			<PanelRow>
				<strong>{__('Start Date:', 'brickslift-ab-testing')}</strong> {formatDate(testDetails._blft_test_start_date)}
			</PanelRow>
			<PanelRow>
				<strong>{__('End Date:', 'brickslift-ab-testing')}</strong> {formatDate(testDetails._blft_test_actual_end_date)}
			</PanelRow>
			*/}
		</div>
	);
};

export default TestInformation;