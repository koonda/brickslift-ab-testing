/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, SelectControl, TextControl } from '@wordpress/components';

const GoalMetricSelector = ({ formData, setFormField, validationErrors }) => {
    // const getFieldError = (fieldName) => validationErrors && validationErrors[fieldName];

    const goalTypeOptions = [
        { label: __('Page Visit', 'brickslift-ab-testing'), value: 'page_visit' },
        { label: __('Element Click', 'brickslift-ab-testing'), value: 'selector_click' }, // Changed from selector_click for clarity
        { label: __('Form Submission', 'brickslift-ab-testing'), value: 'form_submission' },
        { label: __('WooCommerce Add to Cart', 'brickslift-ab-testing'), value: 'wc_add_to_cart' },
        { label: __('Scroll Depth', 'brickslift-ab-testing'), value: 'scroll_depth' },
        { label: __('Time on Page', 'brickslift-ab-testing'), value: 'time_on_page' },
        { label: __('Custom JavaScript Event', 'brickslift-ab-testing'), value: 'custom_js_event' },
    ];

    return (
        <PanelBody title={__('Conversion Goal', 'brickslift-ab-testing')} initialOpen={true}>
            <PanelRow>
                <SelectControl
                    label={__('Goal Type', 'brickslift-ab-testing')}
                    value={formData.goal_type}
                    options={goalTypeOptions}
                    onChange={(value) => setFormField('goal_type', value)}
                />
            </PanelRow>

            {/* Page Visit Fields */}
            {formData.goal_type === 'page_visit' && (
                <>
                    <PanelRow>
                        <TextControl
                            label={__('Target Page URL', 'brickslift-ab-testing')}
                            value={formData.goal_pv_url}
                            onChange={(value) => setFormField('goal_pv_url', value)}
                            placeholder="https://example.com/thank-you"
                            help={__('The URL of the page that signifies a conversion.', 'brickslift-ab-testing')}
                        />
                    </PanelRow>
                    <PanelRow>
                        <SelectControl
                            label={__('URL Match Type', 'brickslift-ab-testing')}
                            value={formData.goal_pv_url_match_type}
                            options={[
                                { label: __('Exact Match', 'brickslift-ab-testing'), value: 'exact' },
                                { label: __('Contains', 'brickslift-ab-testing'), value: 'contains' },
                                { label: __('Starts With', 'brickslift-ab-testing'), value: 'starts_with' },
                                { label: __('Ends With', 'brickslift-ab-testing'), value: 'ends_with' },
                                { label: __('Regex', 'brickslift-ab-testing'), value: 'regex' },
                            ]}
                            onChange={(value) => setFormField('goal_pv_url_match_type', value)}
                        />
                    </PanelRow>
                </>
            )}

            {/* Element Click Fields */}
            {formData.goal_type === 'selector_click' && (
                <PanelRow>
                    <TextControl
                        label={__('Element CSS Selector', 'brickslift-ab-testing')}
                        value={formData.goal_sc_element_selector}
                        onChange={(value) => setFormField('goal_sc_element_selector', value)}
                        placeholder=".my-button, #submit-form"
                        help={__('The CSS selector of the element that, when clicked, signifies a conversion.', 'brickslift-ab-testing')}
                    />
                </PanelRow>
            )}

            {/* Form Submission Fields */}
            {formData.goal_type === 'form_submission' && (
                <>
                    <PanelRow>
                        <TextControl
                            label={__('Form CSS Selector', 'brickslift-ab-testing')}
                            value={formData.goal_fs_form_selector}
                            onChange={(value) => setFormField('goal_fs_form_selector', value)}
                            placeholder="form#contact-form, .wpforms-form"
                            help={__('The CSS selector of the form.', 'brickslift-ab-testing')}
                        />
                    </PanelRow>
                    <PanelRow>
                        <SelectControl
                            label={__('Submission Trigger', 'brickslift-ab-testing')}
                            value={formData.goal_fs_trigger}
                            options={[
                                { label: __('Form Submit Event (Recommended)', 'brickslift-ab-testing'), value: 'submit_event' },
                                { label: __('Thank You Page URL', 'brickslift-ab-testing'), value: 'thank_you_url' },
                                { label: __('Success Message Class', 'brickslift-ab-testing'), value: 'success_class' },
                            ]}
                            onChange={(value) => setFormField('goal_fs_trigger', value)}
                        />
                    </PanelRow>
                    {formData.goal_fs_trigger === 'thank_you_url' && (
                        <PanelRow>
                            <TextControl
                                label={__('Thank You Page URL', 'brickslift-ab-testing')}
                                value={formData.goal_fs_thank_you_url}
                                onChange={(value) => setFormField('goal_fs_thank_you_url', value)}
                                placeholder="https://example.com/form-success"
                            />
                        </PanelRow>
                    )}
                    {formData.goal_fs_trigger === 'success_class' && (
                        <PanelRow>
                            <TextControl
                                label={__('Success Message CSS Class', 'brickslift-ab-testing')}
                                value={formData.goal_fs_success_class}
                                onChange={(value) => setFormField('goal_fs_success_class', value)}
                                placeholder=".form-success-message"
                            />
                        </PanelRow>
                    )}
                </>
            )}

            {/* WooCommerce Add to Cart Fields */}
            {formData.goal_type === 'wc_add_to_cart' && (
                <>
                    <PanelRow>
                        <label>
                            <input
                                type="checkbox"
                                checked={!!formData.goal_wc_any_product}
                                onChange={(e) => setFormField('goal_wc_any_product', e.target.checked)}
                            />
                            {__('Any Product Added to Cart', 'brickslift-ab-testing')}
                        </label>
                    </PanelRow>
                    {!formData.goal_wc_any_product && (
                        <PanelRow>
                            <TextControl
                                label={__('Specific Product ID', 'brickslift-ab-testing')}
                                type="number"
                                value={formData.goal_wc_product_id}
                                onChange={(value) => setFormField('goal_wc_product_id', value)}
                                placeholder="123"
                                help={__('Enter the WooCommerce Product ID.', 'brickslift-ab-testing')}
                            />
                        </PanelRow>
                    )}
                </>
            )}

            {/* Scroll Depth Fields */}
            {formData.goal_type === 'scroll_depth' && (
                <PanelRow>
                    <TextControl
                        label={__('Scroll Depth Percentage', 'brickslift-ab-testing')}
                        type="number"
                        min="1"
                        max="100"
                        value={formData.goal_sd_percentage}
                        onChange={(value) => setFormField('goal_sd_percentage', value)}
                        placeholder="75"
                        help={__('Percentage of the page scrolled.', 'brickslift-ab-testing')}
                    />
                </PanelRow>
            )}

            {/* Time on Page Fields */}
            {formData.goal_type === 'time_on_page' && (
                <PanelRow>
                    <TextControl
                        label={__('Time on Page (seconds)', 'brickslift-ab-testing')}
                        type="number"
                        min="1"
                        value={formData.goal_top_seconds}
                        onChange={(value) => setFormField('goal_top_seconds', value)}
                        placeholder="60"
                        help={__('Minimum time spent on the page.', 'brickslift-ab-testing')}
                    />
                </PanelRow>
            )}

            {/* Custom JS Event Fields */}
            {formData.goal_type === 'custom_js_event' && (
                <PanelRow>
                    <TextControl
                        label={__('Custom Event Name', 'brickslift-ab-testing')}
                        value={formData.goal_cje_event_name}
                        onChange={(value) => setFormField('goal_cje_event_name', value)}
                        placeholder="myCustomConversionEvent"
                        help={__('The name of the JavaScript event to listen for (e.g., triggered by dataLayer.push).', 'brickslift-ab-testing')}
                    />
                </PanelRow>
            )}
        </PanelBody>
    );
};

export default GoalMetricSelector;