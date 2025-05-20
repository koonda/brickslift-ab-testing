/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, Panel, PanelBody, PanelRow, TextControl, SelectControl, TextareaControl, TabPanel, DatePicker } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import './TestEditor.scss'; // For styling
import StatisticsView from './StatisticsView'; // Import the StatisticsView component

// Mocking a router or way to get testId for now
// In a real app, this would come from URL params (e.g., react-router)
const getTestIdFromSomewhere = () => {
	// For now, let's assume we are creating a new test or editing test ID 1
	// This needs to be replaced with actual routing logic.
	// const urlParams = new URLSearchParams(window.location.search);
	// return urlParams.get('test_id');
	return null; // null for new test, or an ID for editing
};


const TestEditor = ({ testId: propTestId, onSave, onCancel }) => {
	const [testId, setTestId] = useState(propTestId || getTestIdFromSomewhere());
	const [title, setTitle] = useState('');
	const [status, setStatus] = useState('publish'); // WP Post Status
	const [blftStatus, setBlftStatus] = useState('draft'); // Custom A/B Test Status
	const [description, setDescription] = useState('');
	const [variants, setVariants] = useState([{ id: 'initial-variant', name: 'Variant A', distribution: 100 }]); // Placeholder ID
	const [isLoading, setIsLoading] = useState(false);
	const [isSaving, setIsSaving] = useState(false);
	const [error, setError] = useState(null);
	const [notice, setNotice] = useState(null);
	const [activeTab, setActiveTab] = useState('settings');

	// Goal state
	const [goalType, setGoalType] = useState('page_visit');
	const [goalPvUrl, setGoalPvUrl] = useState('');
	const [goalPvUrlMatchType, setGoalPvUrlMatchType] = useState('exact');
	const [goalScElementSelector, setGoalScElementSelector] = useState('');
	const [goalFsFormSelector, setGoalFsFormSelector] = useState('');
	const [goalFsTrigger, setGoalFsTrigger] = useState('submit_event');
	const [goalFsThankYouUrl, setGoalFsThankYouUrl] = useState('');
	const [goalFsSuccessClass, setGoalFsSuccessClass] = useState('');
	const [goalWcAnyProduct, setGoalWcAnyProduct] = useState(false);
	const [goalWcProductId, setGoalWcProductId] = useState('');
	const [goalSdPercentage, setGoalSdPercentage] = useState('');
	const [goalTopSeconds, setGoalTopSeconds] = useState('');
	const [goalCjeEventName, setGoalCjeEventName] = useState('');

	// GDPR and Global Tracking State
	const [runTrackingGlobally, setRunTrackingGlobally] = useState(false);
	const [gdprConsentRequired, setGdprConsentRequired] = useState(false);
	const [gdprConsentMechanism, setGdprConsentMechanism] = useState('none');
	const [gdprConsentKeyName, setGdprConsentKeyName] = useState('');
	const [gdprConsentKeyValue, setGdprConsentKeyValue] = useState('');

	// Test Duration and End Condition State
	const [testDurationType, setTestDurationType] = useState('manual'); // manual, fixed_days, end_date
	const [testDurationDays, setTestDurationDays] = useState('');
	const [testEndDate, setTestEndDate] = useState(null); // Store as Date object or null
	const [testAutoEndCondition, setTestAutoEndCondition] = useState('none'); // none, min_conversions, min_views
	const [testAutoEndValue, setTestAutoEndValue] = useState('');

	const isNewTest = !testId;

	const generateId = () => {
		if (typeof wp !== 'undefined' && typeof wp. Passworts !== 'undefined' && typeof wp. Passworts.generate === 'function') {
			return wp. Passworts.generate();
		}
		// Simple fallback if wp. Passworts.generate is not available
		return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
	};


	useEffect(() => {
		// Initialize variants with a generated ID if wp. Passworts is not yet available or for new tests
		if (variants.length === 1 && variants[0].id === 'initial-variant') {
			setVariants([{ id: generateId(), name: 'Variant A', distribution: 100 }]);
		}

		if (testId) {
			setIsLoading(true);
			apiFetch({ path: `/blft/v1/tests/${testId}?context=edit` })
				.then((data) => {
					setTitle(data.title?.raw || '');
					setStatus(data.status || 'publish');
					setBlftStatus(data.blft_status || 'draft');
					setDescription(data.description || '');
					setVariants(data.variants && data.variants.length > 0 ? data.variants.map(v => ({...v, id: v.id || generateId() })) : [{ id: generateId(), name: 'Variant A', distribution: 100 }]);
					// Load goal data
					setGoalType(data.goal_type || 'page_visit');
					setGoalPvUrl(data.goal_pv_url || '');
					setGoalPvUrlMatchType(data.goal_pv_url_match_type || 'exact');
					setGoalScElementSelector(data.goal_sc_element_selector || '');
					setGoalFsFormSelector(data.goal_fs_form_selector || '');
					setGoalFsTrigger(data.goal_fs_trigger || 'submit_event');
					setGoalFsThankYouUrl(data.goal_fs_thank_you_url || '');
					setGoalFsSuccessClass(data.goal_fs_success_class || '');
					setGoalWcAnyProduct(!!data.goal_wc_any_product);
					setGoalWcProductId(data.goal_wc_product_id || '');
					setGoalSdPercentage(data.goal_sd_percentage || '');
					setGoalTopSeconds(data.goal_top_seconds || '');
					setGoalCjeEventName(data.goal_cje_event_name || '');

					// Load GDPR and Global Tracking data
					setRunTrackingGlobally(!!data.run_tracking_globally);
					setGdprConsentRequired(!!data.gdpr_consent_required);
					setGdprConsentMechanism(data.gdpr_consent_mechanism || 'none');
					setGdprConsentKeyName(data.gdpr_consent_key_name || '');
					setGdprConsentKeyValue(data.gdpr_consent_key_value || '');

					// Load Test Duration and End Condition data
					setTestDurationType(data.meta?._blft_test_duration_type || 'manual');
					setTestDurationDays(data.meta?._blft_test_duration_days || '');
					setTestEndDate(data.meta?._blft_test_end_date ? new Date(data.meta._blft_test_end_date) : null);
					setTestAutoEndCondition(data.meta?._blft_test_auto_end_condition || 'none');
					setTestAutoEndValue(data.meta?._blft_test_auto_end_value || '');

					setIsLoading(false);
				})
				.catch((err) => {
					setError(err.message || __('Failed to load test data.', 'brickslift-ab-testing'));
					setIsLoading(false);
				});
		} else {
			// For new tests, ensure variants have a generated ID
			setVariants([{ id: generateId(), name: 'Variant A', distribution: 100 }]);
			// Set default duration/end condition for new tests (already handled by useState defaults)
		}
	}, [testId]);

	const handleAddVariant = () => {
		setVariants([...variants, { id: generateId(), name: `Variant ${String.fromCharCode(65 + variants.length)}`, distribution: 0 }]);
	};

	const handleVariantChange = (index, field, value) => {
		const newVariants = [...variants];
		if (field === 'distribution') {
			newVariants[index][field] = parseInt(value, 10) || 0;
		} else {
			newVariants[index][field] = value;
		}
		setVariants(newVariants);
	};

	const handleRemoveVariant = (index) => {
		const newVariants = variants.filter((_, i) => i !== index);
		setVariants(newVariants);
	};

	const validateVariantsDistribution = () => {
		const totalDistribution = variants.reduce((sum, v) => sum + (parseInt(v.distribution, 10) || 0), 0);
		if (variants.length > 0 && totalDistribution !== 100) {
			setError(__('Total distribution for variants must be 100%.', 'brickslift-ab-testing'));
			return false;
		}
		setError(null);
		return true;
	};

	const handleSave = () => {
		if (!validateVariantsDistribution()) {
			return;
		}
		setIsSaving(true);
		setError(null);
		setNotice(null);

		const payload = {
			title: title,
			status: status,
			blft_status: blftStatus,
			description: description,
			variants: variants,
			// Goal data
			goal_type: goalType,
			goal_pv_url: goalPvUrl,
			goal_pv_url_match_type: goalPvUrlMatchType,
			goal_sc_element_selector: goalScElementSelector,
			goal_fs_form_selector: goalFsFormSelector,
			goal_fs_trigger: goalFsTrigger,
			goal_fs_thank_you_url: goalFsThankYouUrl,
			goal_fs_success_class: goalFsSuccessClass,
			goal_wc_any_product: goalWcAnyProduct,
			goal_wc_product_id: goalWcProductId,
			goal_sd_percentage: goalSdPercentage,
			goal_top_seconds: goalTopSeconds,
			goal_cje_event_name: goalCjeEventName,
			// GDPR and Global Tracking data
			run_tracking_globally: runTrackingGlobally,
			gdpr_consent_required: gdprConsentRequired,
			gdpr_consent_mechanism: gdprConsentMechanism,
			gdpr_consent_key_name: gdprConsentKeyName,
			gdpr_consent_key_value: gdprConsentKeyValue,
			meta: {
				_blft_test_duration_type: testDurationType,
				_blft_test_duration_days: testDurationType === 'fixed_days' ? parseInt(testDurationDays, 10) || 0 : '',
				_blft_test_end_date: testDurationType === 'end_date' && testEndDate ? testEndDate.toISOString().split('T')[0] : '', // YYYY-MM-DD
				_blft_test_auto_end_condition: testAutoEndCondition,
				_blft_test_auto_end_value: testAutoEndCondition !== 'none' ? parseInt(testAutoEndValue, 10) || 0 : '',
			}
		};

		const path = isNewTest ? '/blft/v1/tests' : `/blft/v1/tests/${testId}`;
		const method = isNewTest ? 'POST' : 'PUT';

		apiFetch({ path, method, data: payload })
			.then((response) => {
				setIsSaving(false);
				setNotice(isNewTest ? __('Test created successfully!', 'brickslift-ab-testing') : __('Test updated successfully!', 'brickslift-ab-testing'));
				if (isNewTest && response.id) {
					setTestId(response.id); // Update state if it was a new test
                    setActiveTab('settings'); // Switch back to settings if it was a new test, stats will be enabled
				}
				if (onSave) onSave(response);
			})
			.catch((err) => {
				setIsSaving(false);
				setError(err.message || (isNewTest ? __('Failed to create test.', 'brickslift-ab-testing') : __('Failed to update test.', 'brickslift-ab-testing')));
			});
	};

	if (isLoading && testId) {
		return <p>{__('Loading test editor...', 'brickslift-ab-testing')}</p>;
	}

	return (
		<div className="blft-test-editor">
			<h2>{isNewTest ? __('Create New A/B Test', 'brickslift-ab-testing') : __('Edit A/B Test', 'brickslift-ab-testing')}</h2>
			{error && <div className="notice notice-error is-dismissible"><p>{error}</p></div>}
			{notice && <div className="notice notice-success is-dismissible"><p>{notice}</p></div>}

			<TabPanel
				className="blft-test-editor-tabs"
				activeClass="is-active"
				orientation="horizontal"
				activeTab={activeTab}
				onSelect={setActiveTab}
				tabs={[
					{
						name: 'settings',
						title: __('Settings', 'brickslift-ab-testing'),
						className: 'blft-tab-settings',
					},
					{
						name: 'statistics',
						title: __('Statistics', 'brickslift-ab-testing'),
						className: 'blft-tab-statistics',
						disabled: isNewTest, // Disable for new tests
					},
				]}
			>
				{(tab) => (
					<div className="blft-tab-content">
						{tab.name === 'settings' && (
							<Panel>
								<PanelBody title={__('Basic Information', 'brickslift-ab-testing')} initialOpen={true}>
									<PanelRow>
										<TextControl
											label={__('Test Name / Title', 'brickslift-ab-testing')}
											value={title}
											onChange={setTitle}
											help={__('Enter a descriptive name for your test.', 'brickslift-ab-testing')}
										/>
									</PanelRow>
									<PanelRow>
										<TextareaControl
											label={__('Description', 'brickslift-ab-testing')}
											value={description}
											onChange={setDescription}
											help={__('Optional: Briefly describe the purpose or hypothesis of this test.', 'brickslift-ab-testing')}
										/>
									</PanelRow>
									<PanelRow>
										<SelectControl
											label={__('A/B Test Status', 'brickslift-ab-testing')}
											value={blftStatus}
											options={[
												{ label: __('Draft', 'brickslift-ab-testing'), value: 'draft' },
												{ label: __('Running', 'brickslift-ab-testing'), value: 'running' },
												{ label: __('Paused', 'brickslift-ab-testing'), value: 'paused' },
												{ label: __('Completed', 'brickslift-ab-testing'), value: 'completed' },
											]}
											onChange={setBlftStatus}
										/>
									</PanelRow>
									{/* WordPress Post Status - might be controlled differently or hidden depending on workflow */}
									<PanelRow>
										<SelectControl
											label={__('WordPress Post Status', 'brickslift-ab-testing')}
											value={status}
											options={[
												{ label: __('Draft', 'brickslift-ab-testing'), value: 'draft' },
												{ label: __('Published', 'brickslift-ab-testing'), value: 'publish' },
												// Add other relevant WP statuses if needed e.g. private
											]}
											onChange={setStatus}
											help={__('Typically, tests are "Published" to be active, or "Draft".', 'brickslift-ab-testing')}
										/>
									</PanelRow>
								</PanelBody>

								<PanelBody title={__('Variants', 'brickslift-ab-testing')} initialOpen={true}>
									{variants.map((variant, index) => (
										<PanelRow key={variant.id || index} className="blft-variant-row">
											<TextControl
												label={__('Variant Name', 'brickslift-ab-testing')}
												value={variant.name}
												onChange={(val) => handleVariantChange(index, 'name', val)}
												className="blft-variant-name"
											/>
											<TextControl
												label={__('Distribution (%)', 'brickslift-ab-testing')}
												type="number"
												min="0"
												max="100"
												value={variant.distribution}
												onChange={(val) => handleVariantChange(index, 'distribution', val)}
												className="blft-variant-distribution"
											/>
											<Button isLink isDestructive onClick={() => handleRemoveVariant(index)}>
												{__('Remove', 'brickslift-ab-testing')}
											</Button>
										</PanelRow>
									))}
									<Button variant="secondary" onClick={handleAddVariant}>
										{__('Add Variant', 'brickslift-ab-testing')}
									</Button>
								</PanelBody>

								<PanelBody title={__('Conversion Goal', 'brickslift-ab-testing')} initialOpen={true}>
									<PanelRow>
										<SelectControl
											label={__('Goal Type', 'brickslift-ab-testing')}
											value={goalType}
											options={[
												{ label: __('Page Visit', 'brickslift-ab-testing'), value: 'page_visit' },
												{ label: __('Selector Click', 'brickslift-ab-testing'), value: 'selector_click' },
												{ label: __('Form Submission', 'brickslift-ab-testing'), value: 'form_submission' },
												{ label: __('WooCommerce Add to Cart', 'brickslift-ab-testing'), value: 'wc_add_to_cart' },
												{ label: __('Scroll Depth', 'brickslift-ab-testing'), value: 'scroll_depth' },
												{ label: __('Time on Page', 'brickslift-ab-testing'), value: 'time_on_page' },
												{ label: __('Custom JavaScript Event', 'brickslift-ab-testing'), value: 'custom_js_event' },
											]}
											onChange={setGoalType}
										/>
									</PanelRow>

									{/* Page Visit Fields */}
									{goalType === 'page_visit' && (
										<>
											<PanelRow>
												<TextControl
													label={__('Target Page URL', 'brickslift-ab-testing')}
													value={goalPvUrl}
													onChange={setGoalPvUrl}
													placeholder="https://example.com/thank-you"
												/>
											</PanelRow>
											<PanelRow>
												<SelectControl
													label={__('URL Match Type', 'brickslift-ab-testing')}
													value={goalPvUrlMatchType}
													options={[
														{ label: __('Exact Match', 'brickslift-ab-testing'), value: 'exact' },
														{ label: __('Contains', 'brickslift-ab-testing'), value: 'contains' },
														{ label: __('Starts With', 'brickslift-ab-testing'), value: 'starts_with' },
														{ label: __('Ends With', 'brickslift-ab-testing'), value: 'ends_with' },
														{ label: __('Regex', 'brickslift-ab-testing'), value: 'regex' },
													]}
													onChange={setGoalPvUrlMatchType}
												/>
											</PanelRow>
										</>
									)}

									{/* Selector Click Fields */}
									{goalType === 'selector_click' && (
										<PanelRow>
											<TextControl
												label={__('Element CSS Selector', 'brickslift-ab-testing')}
												value={goalScElementSelector}
												onChange={setGoalScElementSelector}
												placeholder=".my-button, #submit-form"
											/>
										</PanelRow>
									)}

									{/* Form Submission Fields */}
									{goalType === 'form_submission' && (
										<>
											<PanelRow>
												<TextControl
													label={__('Form CSS Selector', 'brickslift-ab-testing')}
													value={goalFsFormSelector}
													onChange={setGoalFsFormSelector}
													placeholder="form#contact-form, .wpforms-form"
												/>
											</PanelRow>
											<PanelRow>
												<SelectControl
													label={__('Submission Trigger', 'brickslift-ab-testing')}
													value={goalFsTrigger}
													options={[
														{ label: __('Form Submit Event (Recommended)', 'brickslift-ab-testing'), value: 'submit_event' },
														{ label: __('Thank You Page URL', 'brickslift-ab-testing'), value: 'thank_you_url' },
														{ label: __('Success Message Class', 'brickslift-ab-testing'), value: 'success_class' },
													]}
													onChange={setGoalFsTrigger}
												/>
											</PanelRow>
											{goalFsTrigger === 'thank_you_url' && (
												<PanelRow>
													<TextControl
														label={__('Thank You Page URL', 'brickslift-ab-testing')}
														value={goalFsThankYouUrl}
														onChange={setGoalFsThankYouUrl}
														placeholder="https://example.com/form-success"
													/>
												</PanelRow>
											)}
											{goalFsTrigger === 'success_class' && (
												<PanelRow>
													<TextControl
														label={__('Success Message CSS Class', 'brickslift-ab-testing')}
														value={goalFsSuccessClass}
														onChange={setGoalFsSuccessClass}
														placeholder=".form-success-message"
													/>
												</PanelRow>
											)}
										</>
									)}

									{/* WooCommerce Add to Cart Fields */}
									{goalType === 'wc_add_to_cart' && (
										<>
											<PanelRow>
												<label>
													<input
														type="checkbox"
														checked={goalWcAnyProduct}
														onChange={(e) => setGoalWcAnyProduct(e.target.checked)}
													/>
													{__('Any Product', 'brickslift-ab-testing')}
												</label>
											</PanelRow>
											{!goalWcAnyProduct && (
												<PanelRow>
													<TextControl
														label={__('Specific Product ID', 'brickslift-ab-testing')}
														type="number"
														value={goalWcProductId}
														onChange={setGoalWcProductId}
														placeholder="123"
													/>
												</PanelRow>
											)}
										</>
									)}

									{/* Scroll Depth Fields */}
									{goalType === 'scroll_depth' && (
										<PanelRow>
											<TextControl
												label={__('Scroll Depth Percentage', 'brickslift-ab-testing')}
												type="number"
												min="0"
												max="100"
												value={goalSdPercentage}
												onChange={setGoalSdPercentage}
												placeholder="75"
											/>
										</PanelRow>
									)}

									{/* Time on Page Fields */}
									{goalType === 'time_on_page' && (
										<PanelRow>
											<TextControl
												label={__('Time on Page (seconds)', 'brickslift-ab-testing')}
												type="number"
												min="0"
												value={goalTopSeconds}
												onChange={setGoalTopSeconds}
												placeholder="60"
											/>
										</PanelRow>
									)}

									{/* Custom JS Event Fields */}
									{goalType === 'custom_js_event' && (
										<PanelRow>
											<TextControl
												label={__('Custom Event Name', 'brickslift-ab-testing')}
												value={goalCjeEventName}
												onChange={setGoalCjeEventName}
												placeholder="myCustomConversionEvent"
											/>
										</PanelRow>
									)}
								</PanelBody>

								<PanelBody title={__('Tracking & Consent', 'brickslift-ab-testing')} initialOpen={false}>
									<PanelRow>
										<label htmlFor="blft-runTrackingGlobally" style={{ display: 'flex', alignItems: 'center', gap: '8px', width: '100%' }}>
											<input
												type="checkbox"
												id="blft-runTrackingGlobally"
												checked={runTrackingGlobally}
												onChange={(e) => setRunTrackingGlobally(e.target.checked)}
											/>
											{__('Run tracking script on all pages', 'brickslift-ab-testing')}
										</label>
										<p className="components-form-token-field__help" style={{ marginTop: '4px', width: '100%' }}>
											{__('If unchecked, the tracking script only runs on pages where the A/B test element is present. Check this for goals like "Time on Page" or "Page Visit" on pages without the test element itself, if those pages are part of the user journey you want to track for this test.', 'brickslift-ab-testing')}
										</p>
									</PanelRow>
									<hr style={{ margin: '1em 0'}} />
									<PanelRow>
										<label htmlFor="blft-gdprConsentRequired" style={{ display: 'flex', alignItems: 'center', gap: '8px', width: '100%' }}>
											<input
												type="checkbox"
												id="blft-gdprConsentRequired"
												checked={gdprConsentRequired}
												onChange={(e) => setGdprConsentRequired(e.target.checked)}
											/>
											{__('GDPR Consent Required', 'brickslift-ab-testing')}
										</label>
										<p className="components-form-token-field__help" style={{ marginTop: '4px', width: '100%' }}>
											{__('If checked, tracking will only activate if consent is detected.', 'brickslift-ab-testing')}
										</p>
									</PanelRow>
									{gdprConsentRequired && (
										<>
											<PanelRow>
												<SelectControl
													label={__('Consent Mechanism', 'brickslift-ab-testing')}
													value={gdprConsentMechanism}
													options={[
														{ label: __('None (Plugin does not check, rely on external consent)', 'brickslift-ab-testing'), value: 'none' },
														{ label: __('Cookie Key Present', 'brickslift-ab-testing'), value: 'cookie_key' },
														// Future: { label: __('JavaScript Variable', 'brickslift-ab-testing'), value: 'js_variable' },
													]}
													onChange={setGdprConsentMechanism}
												/>
											</PanelRow>
											{gdprConsentMechanism === 'cookie_key' && (
												<>
													<PanelRow>
														<TextControl
															label={__('Consent Cookie Key Name', 'brickslift-ab-testing')}
															value={gdprConsentKeyName}
															onChange={setGdprConsentKeyName}
															placeholder="gdpr_consent_given"
														/>
													</PanelRow>
													<PanelRow>
														<TextControl
															label={__('Consent Cookie Key Value (Optional)', 'brickslift-ab-testing')}
															value={gdprConsentKeyValue}
															onChange={setGdprConsentKeyValue}
															placeholder="yes"
															help={__('If specified, the cookie must have this value. If empty, any value means consent.', 'brickslift-ab-testing')}
														/>
													</PanelRow>
												</>
											)}
											{/* Add JS Variable fields if that mechanism is implemented */}
										</>
									)}
								</PanelBody>
							</Panel>
						)}
						{tab.name === 'statistics' && !isNewTest && (
							<StatisticsView testId={testId} testStatus={blftStatus} />
						)}
						{tab.name === 'statistics' && isNewTest && (
                             <div style={{ padding: '20px' }}>
                                <p>{__('Statistics are not available for new tests. Please save the test first.', 'brickslift-ab-testing')}</p>
                             </div>
                        )}
					</div>
				)}
			</TabPanel>

			<div className="blft-test-editor-actions">
				<Button
					variant="primary"
					onClick={handleSave}
					isBusy={isSaving}
					disabled={isSaving || (activeTab === 'statistics' && !isNewTest) } // Disable save if on stats tab (unless it's a new test, though stats tab itself is disabled then)
				>
					{isNewTest ? __('Create Test', 'brickslift-ab-testing') : __('Save Changes', 'brickslift-ab-testing')}
				</Button>
				{onCancel && (
					<Button variant="tertiary" onClick={onCancel} disabled={isSaving}>
						{__('Cancel', 'brickslift-ab-testing')}
					</Button>
				)}
			</div>
		</div>
	);
};

export default TestEditor;