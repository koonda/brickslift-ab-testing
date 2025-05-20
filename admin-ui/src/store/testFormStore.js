import create from 'zustand';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

// Helper to generate unique IDs for variants if needed client-side
const generateId = () => {
    if (typeof wp !== 'undefined' && typeof wp.passwordUtils !== 'undefined' && typeof wp.passwordUtils.generate === 'function') {
        return wp.passwordUtils.generate();
    }
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
};

const initialFormData = {
    title: '',
    status: 'publish', // Default WordPress post status
    blft_status: 'draft', // Default A/B test status
    description: '',
    hypothesis: '', // New field
    variants: [{ id: generateId(), name: 'Variant A', distribution: 100, content_url: '' }], // Added content_url placeholder
    // Goal fields
    goal_type: 'page_visit',
    goal_pv_url: '',
    goal_pv_url_match_type: 'exact',
    goal_sc_element_selector: '',
    goal_fs_form_selector: '',
    goal_fs_trigger: 'submit_event',
    goal_fs_thank_you_url: '',
    goal_fs_success_class: '',
    goal_wc_any_product: false,
    goal_wc_product_id: '',
    goal_sd_percentage: '',
    goal_top_seconds: '',
    goal_cje_event_name: '',
    // GDPR and Global Tracking
    run_tracking_globally: false,
    gdpr_consent_required: false,
    gdpr_consent_mechanism: 'none',
    gdpr_consent_key_name: '',
    gdpr_consent_key_value: '',
    // Test Schedule
    start_date: null, // Will be formatted as YYYY-MM-DD for API
    end_date: null, // Will be formatted as YYYY-MM-DD for API
    test_duration_type: 'manual',
    test_duration_days: '',
    test_auto_end_condition: 'none',
    test_auto_end_value: '',
    // Meta fields that might be directly on the payload or under 'meta'
    // Ensure payload structure matches backend expectations
};

const useTestFormStore = create((set, get) => ({
    testId: null,
    formData: { ...initialFormData },
    isLoading: false,
    isSaving: false,
    error: null,
    notice: null,
    validationErrors: {},

    actions: {
        initializeForm: async (testIdToLoad) => {
            set({ isLoading: true, error: null, notice: null, testId: testIdToLoad, validationErrors: {} });
            if (testIdToLoad) {
                try {
                    const data = await apiFetch({ path: `/blft/v1/tests/${testIdToLoad}?context=edit` });
                    set({
                        formData: {
                            title: data.title?.raw || '',
                            status: data.status || 'publish',
                            blft_status: data.blft_status || 'draft',
                            description: data.meta?._blft_description || data.description || '', // Prefer meta if available
                            hypothesis: data.meta?._blft_hypothesis || '', // Prefer meta
                            variants: data.meta?._blft_variants && data.meta._blft_variants.length > 0
                                ? data.meta._blft_variants.map(v => ({ ...v, id: v.id || generateId() }))
                                : [{ id: generateId(), name: 'Variant A', distribution: 100, content_url: '' }],
                            goal_type: data.meta?._blft_goal_type || 'page_visit',
                            goal_pv_url: data.meta?._blft_goal_pv_url || '',
                            goal_pv_url_match_type: data.meta?._blft_goal_pv_url_match_type || 'exact',
                            goal_sc_element_selector: data.meta?._blft_goal_sc_element_selector || '',
                            goal_fs_form_selector: data.meta?._blft_goal_fs_form_selector || '',
                            goal_fs_trigger: data.meta?._blft_goal_fs_trigger || 'submit_event',
                            goal_fs_thank_you_url: data.meta?._blft_goal_fs_thank_you_url || '',
                            goal_fs_success_class: data.meta?._blft_goal_fs_success_class || '',
                            goal_wc_any_product: !!data.meta?._blft_goal_wc_any_product,
                            goal_wc_product_id: data.meta?._blft_goal_wc_product_id || '',
                            goal_sd_percentage: data.meta?._blft_goal_sd_percentage || '',
                            goal_top_seconds: data.meta?._blft_goal_top_seconds || '',
                            goal_cje_event_name: data.meta?._blft_goal_cje_event_name || '',
                            run_tracking_globally: !!data.meta?._blft_run_tracking_globally,
                            gdpr_consent_required: !!data.meta?._blft_gdpr_consent_required,
                            gdpr_consent_mechanism: data.meta?._blft_gdpr_consent_mechanism || 'none',
                            gdpr_consent_key_name: data.meta?._blft_gdpr_consent_key_name || '',
                            gdpr_consent_key_value: data.meta?._blft_gdpr_consent_key_value || '',
                            start_date: data.meta?._blft_start_date || null,
                            end_date: data.meta?._blft_end_date || null,
                            test_duration_type: data.meta?._blft_test_duration_type || 'manual',
                            test_duration_days: data.meta?._blft_test_duration_days || '',
                            test_auto_end_condition: data.meta?._blft_test_auto_end_condition || 'none',
                            test_auto_end_value: data.meta?._blft_test_auto_end_value || '',
                        },
                        isLoading: false,
                    });
                } catch (err) {
                    set({ error: err.message || __('Failed to load test data.', 'brickslift-ab-testing'), isLoading: false });
                }
            } else {
                // Reset to initial state for a new test, ensuring variants have unique IDs
                const newInitialVariants = [{ id: generateId(), name: 'Variant A', distribution: 100, content_url: '' }];
                if (initialFormData.variants.length === 1 && initialFormData.variants[0].id === newInitialVariants[0].id) {
                     // if generateId produced same id, regenerate
                    newInitialVariants[0].id = generateId();
                }
                set({ formData: {...initialFormData, variants: newInitialVariants }, isLoading: false });
            }
        },

        setFormField: (field, value) => {
            set((state) => ({
                formData: { ...state.formData, [field]: value },
                validationErrors: { ...state.validationErrors, [field]: null }, // Clear validation error for this field
                notice: null, // Clear previous success notices on new input
                error: null, // Clear previous save errors on new input
            }));
        },

        addVariant: () => {
            set((state) => ({
                formData: {
                    ...state.formData,
                    variants: [
                        ...state.formData.variants,
                        { id: generateId(), name: `Variant ${String.fromCharCode(65 + state.formData.variants.length)}`, distribution: 0, content_url: '' },
                    ],
                },
            }));
        },

        updateVariant: (index, field, value) => {
            set((state) => {
                const newVariants = [...state.formData.variants];
                if (field === 'distribution') {
                    newVariants[index][field] = parseInt(value, 10) || 0;
                } else {
                    newVariants[index][field] = value;
                }
                return { formData: { ...state.formData, variants: newVariants } };
            });
        },

        removeVariant: (index) => {
            set((state) => ({
                formData: {
                    ...state.formData,
                    variants: state.formData.variants.filter((_, i) => i !== index),
                },
            }));
        },

        validateForm: () => {
            const { formData } = get();
            const errors = {};
            if (!formData.title.trim()) {
                errors.title = __('Test Name is required.', 'brickslift-ab-testing');
            }
            const totalDistribution = formData.variants.reduce((sum, v) => sum + (parseInt(v.distribution, 10) || 0), 0);
            if (formData.variants.length > 0 && totalDistribution !== 100) {
                errors.variants = __('Total distribution for variants must be 100%.', 'brickslift-ab-testing');
            }
            formData.variants.forEach((variant, index) => {
                if (!variant.name.trim()) {
                    if (!errors.variantNames) errors.variantNames = [];
                    errors.variantNames[index] = __('Variant name is required.', 'brickslift-ab-testing');
                }
            });

            // Add more validation rules as needed (e.g., for goal fields, schedule)

            set({ validationErrors: errors });
            return Object.keys(errors).length === 0;
        },

        submitForm: async () => {
            if (!get().actions.validateForm()) {
                set({ error: __("Please correct the errors in the form.", 'brickslift-ab-testing')});
                return null;
            }

            set({ isSaving: true, error: null, notice: null });
            const { testId, formData } = get();

            // Prepare payload, ensuring all relevant fields are included and structured as the API expects
            // The backend expects _blft_variants, _blft_description, _blft_hypothesis etc. in meta for PUT,
            // but might accept them at top level for POST. Check API docs.
            // For now, we assume the API handles direct properties and a 'meta' object.
            const payload = {
                title: formData.title,
                status: formData.status, // WP Post status
                // These will be sent in 'meta' for consistency with how they are often stored
                meta: {
                    _blft_status: formData.blft_status, // Custom A/B test status
                    _blft_description: formData.description,
                    _blft_hypothesis: formData.hypothesis,
                    _blft_variants: formData.variants.map(v => ({ // Ensure variants are structured correctly
                        id: v.id, // Keep client-side ID for mapping if needed, backend might regenerate
                        name: v.name,
                        distribution: parseInt(v.distribution, 10) || 0,
                        content_url: v.content_url // Assuming this is how variant content is linked
                    })),
                    _blft_goal_type: formData.goal_type,
                    _blft_goal_pv_url: formData.goal_pv_url,
                    _blft_goal_pv_url_match_type: formData.goal_pv_url_match_type,
                    _blft_goal_sc_element_selector: formData.goal_sc_element_selector,
                    _blft_goal_fs_form_selector: formData.goal_fs_form_selector,
                    _blft_goal_fs_trigger: formData.goal_fs_trigger,
                    _blft_goal_fs_thank_you_url: formData.goal_fs_thank_you_url,
                    _blft_goal_fs_success_class: formData.goal_fs_success_class,
                    _blft_goal_wc_any_product: formData.goal_wc_any_product,
                    _blft_goal_wc_product_id: formData.goal_wc_product_id,
                    _blft_goal_sd_percentage: formData.goal_sd_percentage,
                    _blft_goal_top_seconds: formData.goal_top_seconds,
                    _blft_goal_cje_event_name: formData.goal_cje_event_name,
                    _blft_run_tracking_globally: formData.run_tracking_globally,
                    _blft_gdpr_consent_required: formData.gdpr_consent_required,
                    _blft_gdpr_consent_mechanism: formData.gdpr_consent_mechanism,
                    _blft_gdpr_consent_key_name: formData.gdpr_consent_key_name,
                    _blft_gdpr_consent_key_value: formData.gdpr_consent_key_value,
                    _blft_start_date: formData.start_date ? new Date(formData.start_date).toISOString().split('T')[0] : '',
                    _blft_end_date: formData.end_date ? new Date(formData.end_date).toISOString().split('T')[0] : '',
                    _blft_test_duration_type: formData.test_duration_type,
                    _blft_test_duration_days: formData.test_duration_type === 'fixed_days' ? parseInt(formData.test_duration_days, 10) || '' : '',
                    _blft_test_auto_end_condition: formData.test_auto_end_condition,
                    _blft_test_auto_end_value: formData.test_auto_end_condition !== 'none' ? parseInt(formData.test_auto_end_value, 10) || '' : '',
                }
            };
            // The API might expect some fields at the top level even for PUT, adjust as necessary.
            // For POST, top-level properties are usually fine.
             if (testId) { // For PUT, ensure top-level fields that CPT supports are there
                payload.blft_status = formData.blft_status;
             }


            const path = testId ? `/blft/v1/tests/${testId}` : '/blft/v1/tests';
            const method = testId ? 'PUT' : 'POST';

            try {
                const response = await apiFetch({ path, method, data: payload });
                set({
                    isSaving: false,
                    notice: testId ? __('Test updated successfully!', 'brickslift-ab-testing') : __('Test created successfully!', 'brickslift-ab-testing'),
                    testId: response.id || testId, // Update testId if it was a new test
                    error: null,
                });
                return response; // Return response for potential chaining (e.g., navigation)
            } catch (err) {
                let errorMessage = err.message;
                if (err.body && typeof err.body.message === 'string') {
                    errorMessage = err.body.message;
                } else if (typeof err.message === 'string') {
                     errorMessage = err.message;
                } else {
                    errorMessage = testId ? __('Failed to update test.', 'brickslift-ab-testing') : __('Failed to create test.', 'brickslift-ab-testing');
                }
                set({ isSaving: false, error: errorMessage });
                return null;
            }
        },
        resetFormState: () => {
            // Resets to the absolute initial state, for example when navigating away or cancelling.
            const newInitialVariants = [{ id: generateId(), name: 'Variant A', distribution: 100, content_url: '' }];
             if (initialFormData.variants.length === 1 && initialFormData.variants[0].id === newInitialVariants[0].id) {
                newInitialVariants[0].id = generateId();
            }
            set({
                testId: null,
                formData: {...initialFormData, variants: newInitialVariants},
                isLoading: false,
                isSaving: false,
                error: null,
                notice: null,
                validationErrors: {},
            });
        },
        clearError: () => set({ error: null }),
        clearNotice: () => set({ notice: null }),
    },
}));

export default useTestFormStore;