/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element'; // Keep useState for local UI state like activeTab
import { Button, Panel, PanelBody, PanelRow, TextControl, SelectControl, TextareaControl, TabPanel, DatePicker } from '@wordpress/components';
// apiFetch is not directly used here anymore, it's in the store.

/**
 * Internal dependencies
 */
import './TestEditor.scss'; // For styling
import StatisticsView from './StatisticsView'; // Import the StatisticsView component
import useTestFormStore from '../../store/testFormStore'; // Import the Zustand store
import TestFormFields from './TestFormFields'; // Import the new component
import VariationManager from './VariationManager'; // Import the VariationManager component
import GoalMetricSelector from './GoalMetricSelector'; // Import the GoalMetricSelector component
import TestScheduleFields from './TestScheduleFields'; // Import the TestScheduleFields component

// const getTestIdFromSomewhere = () => { // This is no longer needed as testId comes from props
// 	return null;
// };


const TestEditor = ({ testId: propTestId, onSaveSuccess, onCancel }) => { // Renamed onSave to onSaveSuccess for clarity
	// Selectors from the store
	const storeTestId = useTestFormStore((state) => state.testId);
	const formData = useTestFormStore((state) => state.formData);
	const isLoading = useTestFormStore((state) => state.isLoading);
	const isSaving = useTestFormStore((state) => state.isSaving);
	const error = useTestFormStore((state) => state.error);
	const notice = useTestFormStore((state) => state.notice);
	const validationErrors = useTestFormStore((state) => state.validationErrors);

	// Actions from the store
	const {
		initializeForm,
		setFormField,
		addVariant,
		updateVariant,
		removeVariant,
		submitForm,
		resetFormState,
		clearError,
		clearNotice
	} = useTestFormStore((state) => state.actions);

	const [activeTab, setActiveTab] = useState('settings'); // Local UI state

	const isNewTest = !storeTestId; // Determined by the store's testId

	// Effect to initialize or reset the form when the component mounts or propTestId changes
	useEffect(() => {
		initializeForm(propTestId);
		// Cleanup function to reset form state when component unmounts or propTestId changes causing re-initialization
		return () => {
			// resetFormState(); // Resetting here might be too aggressive if just switching tabs.
			// Consider resetting only on explicit cancel or navigation away.
		};
	}, [propTestId, initializeForm]);


	const handleSave = async () => {
		clearError(); // Clear previous errors before attempting to save
		clearNotice(); // Clear previous notices
		const savedTestData = await submitForm();
		if (savedTestData && savedTestData.id) { // Ensure we have an ID
			// If onSaveSuccess prop is provided (for navigation), call it with the ID.
			if (onSaveSuccess) {
				onSaveSuccess(savedTestData.id); // Pass only the ID as requested by App.js
			}
			// If it was a new test, the store's testId is now updated.
			// The navigation to detail view is handled by onSaveSuccess.
			// We might still want to switch tab if user stays on page, but navigation takes precedence.
			if (isNewTest) {
		              // setActiveTab('settings'); // Or, if navigation doesn't occur, this might be useful.
		                                      // However, App.js should navigate away.
			}
		}
		// Error/Notice display is handled by the store and rendered below
	};

	const handleCancel = () => {
		resetFormState(); // Reset the store to its initial state
		if (onCancel) {
			onCancel();
		}
	};

	// Helper to get a specific validation error for a field
	const getFieldError = (fieldName) => {
		return validationErrors && validationErrors[fieldName];
	};
    const getVariantFieldError = (index, fieldName) => {
        return validationErrors && validationErrors.variantNames && validationErrors.variantNames[index];
    };


	if (isLoading && !formData.title) { // Show loading only if form data isn't partially loaded
		return <p>{__('Loading test editor...', 'brickslift-ab-testing')}</p>;
	}

	return (
		<div className="blft-test-editor">
			<h2>{isNewTest ? __('Create New A/B Test', 'brickslift-ab-testing') : __('Edit A/B Test', 'brickslift-ab-testing')}</h2>
			{error && <div className="notice notice-error is-dismissible"><p>{error}</p><Button isSmall isLink onClick={clearError}>{__('Dismiss', 'brickslift-ab-testing')}</Button></div>}
			{notice && <div className="notice notice-success is-dismissible"><p>{notice}</p><Button isSmall isLink onClick={clearNotice}>{__('Dismiss', 'brickslift-ab-testing')}</Button></div>}
            {validationErrors.variants && <div className="notice notice-error is-dismissible"><p>{validationErrors.variants}</p></div>}


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
						disabled: isNewTest, // Disable for new tests (when storeTestId is null)
					},
				]}
			>
				{(tab) => (
					<div className="blft-tab-content">
						{tab.name === 'settings' && (
							<Panel>
								<TestFormFields
									formData={formData}
									setFormField={setFormField}
									validationErrors={validationErrors}
								/>
								{/* Status selectors remain here for now, or could be moved to a dedicated settings component */}
								<PanelBody title={__('Test Status Settings', 'brickslift-ab-testing')} initialOpen={true}>
									<PanelRow>
										<SelectControl
											label={__('A/B Test Status', 'brickslift-ab-testing')}
											value={formData.blft_status}
											options={[
												{ label: __('Draft', 'brickslift-ab-testing'), value: 'draft' },
												{ label: __('Running', 'brickslift-ab-testing'), value: 'running' },
												{ label: __('Paused', 'brickslift-ab-testing'), value: 'paused' },
												{ label: __('Completed', 'brickslift-ab-testing'), value: 'completed' },
											]}
											onChange={(value) => setFormField('blft_status', value)}
										/>
									</PanelRow>
									<PanelRow>
										<SelectControl
											label={__('WordPress Post Status', 'brickslift-ab-testing')}
											value={formData.status}
											options={[
												{ label: __('Draft', 'brickslift-ab-testing'), value: 'draft' },
												{ label: __('Published', 'brickslift-ab-testing'), value: 'publish' },
											]}
											onChange={(value) => setFormField('status', value)}
											help={__('Typically, tests are "Published" to be active, or "Draft".', 'brickslift-ab-testing')}
										/>
									</PanelRow>
								</PanelBody>

								<VariationManager
									variants={formData.variants}
									addVariant={addVariant}
									updateVariant={updateVariant}
									removeVariant={removeVariant}
									validationErrors={validationErrors}
								/>

								<GoalMetricSelector
									formData={formData}
									setFormField={setFormField}
									validationErrors={validationErrors}
								/>

								<TestScheduleFields
									formData={formData}
									setFormField={setFormField}
									validationErrors={validationErrors}
								/>

								<PanelBody title={__('Tracking & Consent (GDPR)', 'brickslift-ab-testing')} initialOpen={false}>
									<PanelRow>
										<label>
											<input
												type="checkbox"
												checked={!!formData.run_tracking_globally}
												onChange={(e) => setFormField('run_tracking_globally', e.target.checked)}
											/>
											{__('Run tracking script on all pages', 'brickslift-ab-testing')}
										</label>
										<p className="components-form-token-field__help">
											{__('If unchecked, tracking script only runs on pages with active test variations. Check this if your conversion goal is on a different page than the test variations.', 'brickslift-ab-testing')}
										</p>
									</PanelRow>
									<PanelRow>
										<label>
											<input
												type="checkbox"
												checked={!!formData.gdpr_consent_required}
												onChange={(e) => setFormField('gdpr_consent_required', e.target.checked)}
											/>
											{__('GDPR Consent Required for Tracking', 'brickslift-ab-testing')}
										</label>
									</PanelRow>
									{formData.gdpr_consent_required && (
										<>
											<PanelRow>
												<SelectControl
													label={__('Consent Mechanism', 'brickslift-ab-testing')}
													value={formData.gdpr_consent_mechanism}
													options={[
														{ label: __('None (Assume consent or handle externally)', 'brickslift-ab-testing'), value: 'none' },
														{ label: __('Cookie Value', 'brickslift-ab-testing'), value: 'cookie' },
														{ label: __('JavaScript Variable', 'brickslift-ab-testing'), value: 'js_variable' },
														// Future: Integration with consent plugins
													]}
													onChange={(value) => setFormField('gdpr_consent_mechanism', value)}
												/>
											</PanelRow>
											{(formData.gdpr_consent_mechanism === 'cookie' || formData.gdpr_consent_mechanism === 'js_variable') && (
												<>
													<PanelRow>
														<TextControl
															label={formData.gdpr_consent_mechanism === 'cookie' ? __('Cookie Name', 'brickslift-ab-testing') : __('JS Variable Name', 'brickslift-ab-testing')}
															value={formData.gdpr_consent_key_name}
															onChange={(value) => setFormField('gdpr_consent_key_name', value)}
															placeholder={formData.gdpr_consent_mechanism === 'cookie' ? 'gdpr_consent_given' : 'window.gdprConsentGiven'}
														/>
													</PanelRow>
													<PanelRow>
														<TextControl
															label={__('Required Value for Consent', 'brickslift-ab-testing')}
															value={formData.gdpr_consent_key_value}
															onChange={(value) => setFormField('gdpr_consent_key_value', value)}
															placeholder="true, yes, 1"
															help={__('The value the cookie or variable must have to indicate consent.', 'brickslift-ab-testing')}
														/>
													</PanelRow>
												</>
											)}
										</>
									)}
								</PanelBody>

								<PanelRow className="blft-form-actions">
									<Button variant="primary" onClick={handleSave} isBusy={isSaving} disabled={isSaving || isLoading}>
										{isNewTest ? __('Create Test', 'brickslift-ab-testing') : __('Update Test', 'brickslift-ab-testing')}
									</Button>
									{onCancel && ( // Ensure onCancel is callable
										<Button variant="tertiary" onClick={handleCancel} disabled={isSaving || isLoading}>
											{__('Cancel', 'brickslift-ab-testing')}
										</Button>
									)}
								</PanelRow>
							</Panel>
						)}
						{tab.name === 'statistics' && !isNewTest && storeTestId && (
							<StatisticsView testId={storeTestId} />
						)}
												             {tab.name === 'statistics' && isNewTest && (
												                 <div style={{ padding: '20px' }}>
												                    <p>{__('Statistics are not available for new tests. Please save the test first.', 'brickslift-ab-testing')}</p>
												                 </div>
												            )}
					</div>
				)}
			</TabPanel>
												{/* Action buttons are now part of the settings panel content for better flow */}
		</div>
	);
};

export default TestEditor;