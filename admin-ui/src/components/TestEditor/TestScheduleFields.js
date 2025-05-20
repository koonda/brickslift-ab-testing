/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, SelectControl, TextControl, DatePicker } from '@wordpress/components'; // DatePicker might not be used if TextControl type="date" is preferred

const TestScheduleFields = ({ formData, setFormField, validationErrors }) => {
    // const getFieldError = (fieldName) => validationErrors && validationErrors[fieldName];

    // Helper to handle date changes from TextControl type="date"
    // The value from TextControl type="date" is 'YYYY-MM-DD' string or empty string.
    // The store expects null or 'YYYY-MM-DD'.
    const handleDateChange = (field, value) => {
        setFormField(field, value || null); // Store as null if empty, otherwise the date string
    };

    return (
        <PanelBody title={__('Test Schedule & Duration', 'brickslift-ab-testing')} initialOpen={false}>
            <PanelRow>
                <TextControl
                    label={__('Start Date', 'brickslift-ab-testing')}
                    type="date"
                    value={formData.start_date || ''} // Ensure empty string if null for input
                    onChange={(value) => handleDateChange('start_date', value)}
                    help={__('Optional. If not set, the test can be started manually.', 'brickslift-ab-testing')}
                />
            </PanelRow>
            <PanelRow>
                <SelectControl
                    label={__('Duration Type', 'brickslift-ab-testing')}
                    value={formData.test_duration_type}
                    options={[
                        { label: __('Manual Stop', 'brickslift-ab-testing'), value: 'manual' },
                        { label: __('Fixed Number of Days', 'brickslift-ab-testing'), value: 'fixed_days' },
                        { label: __('Specific End Date', 'brickslift-ab-testing'), value: 'end_date' },
                    ]}
                    onChange={(value) => setFormField('test_duration_type', value)}
                />
            </PanelRow>
            {formData.test_duration_type === 'fixed_days' && (
                <PanelRow>
                    <TextControl
                        label={__('Number of Days', 'brickslift-ab-testing')}
                        type="number"
                        min="1"
                        value={formData.test_duration_days}
                        onChange={(value) => setFormField('test_duration_days', value)}
                        help={__('The test will run for this many days after starting.', 'brickslift-ab-testing')}
                    />
                </PanelRow>
            )}
            {formData.test_duration_type === 'end_date' && (
                <PanelRow>
                    <TextControl
                        label={__('End Date', 'brickslift-ab-testing')}
                        type="date"
                        value={formData.end_date || ''} // Ensure empty string if null
                        onChange={(value) => handleDateChange('end_date', value)}
                        help={__('The test will automatically stop on this date.', 'brickslift-ab-testing')}
                    />
                </PanelRow>
            )}
            <PanelRow>
                <SelectControl
                    label={__('Auto End Condition', 'brickslift-ab-testing')}
                    value={formData.test_auto_end_condition}
                    options={[
                        { label: __('None', 'brickslift-ab-testing'), value: 'none' },
                        { label: __('Minimum Conversions Reached', 'brickslift-ab-testing'), value: 'min_conversions' },
                        { label: __('Minimum Views/Visitors Reached', 'brickslift-ab-testing'), value: 'min_views' },
                    ]}
                    onChange={(value) => setFormField('test_auto_end_condition', value)}
                    help={__('Optionally, end the test automatically when a statistical condition is met.', 'brickslift-ab-testing')}
                />
            </PanelRow>
            {formData.test_auto_end_condition !== 'none' && (
                <PanelRow>
                    <TextControl
                        label={formData.test_auto_end_condition === 'min_conversions' ? __('Minimum Conversions', 'brickslift-ab-testing') : __('Minimum Views/Visitors', 'brickslift-ab-testing')}
                        type="number"
                        min="1"
                        value={formData.test_auto_end_value}
                        onChange={(value) => setFormField('test_auto_end_value', value)}
                    />
                </PanelRow>
            )}
        </PanelBody>
    );
};

export default TestScheduleFields;