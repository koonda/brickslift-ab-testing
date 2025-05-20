/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextControl, TextareaControl } from '@wordpress/components';

const TestFormFields = ({ formData, setFormField, validationErrors }) => {
    const getFieldError = (fieldName) => {
        return validationErrors && validationErrors[fieldName];
    };

    return (
        <PanelBody title={__('Basic Information', 'brickslift-ab-testing')} initialOpen={true}>
            <PanelRow>
                <TextControl
                    label={__('Test Name / Title', 'brickslift-ab-testing')}
                    value={formData.title}
                    onChange={(value) => setFormField('title', value)}
                    help={getFieldError('title') || __('Enter a descriptive name for your test.', 'brickslift-ab-testing')}
                    className={getFieldError('title') ? 'is-invalid' : ''}
                />
            </PanelRow>
            <PanelRow>
                <TextareaControl
                    label={__('Hypothesis', 'brickslift-ab-testing')}
                    value={formData.hypothesis}
                    onChange={(value) => setFormField('hypothesis', value)}
                    help={__('What is the primary hypothesis for this test? (e.g., "Changing the button color to green will increase clicks.")', 'brickslift-ab-testing')}
                />
            </PanelRow>
            <PanelRow>
                <TextareaControl
                    label={__('Description', 'brickslift-ab-testing')}
                    value={formData.description}
                    onChange={(value) => setFormField('description', value)}
                    help={__('Optional: Add more details or context about this test.', 'brickslift-ab-testing')}
                />
            </PanelRow>
            {/* WordPress Post Status and A/B Test Status will remain in TestEditor.js for now,
                as they are slightly different from the core "fields" and might be good to keep
                alongside other general settings if not moved to a dedicated "TestSettings" component later.
                Alternatively, they could be moved here too if preferred.
            */}
        </PanelBody>
    );
};

export default TestFormFields;