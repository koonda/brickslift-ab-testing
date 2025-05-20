/**
 * BricksLift A/B Testing - Frontend Logic
 *
 * Handles variant selection, display, and impression tracking.
 */
(function () {
	'use strict';

	const BLFT_VISITOR_HASH_KEY = 'blft_visitor_hash';
	const BLFT_TEST_VARIANT_PREFIX = 'blft_test_variant_'; // test_id will be appended
	const BLFT_IMPRESSION_TRACKED_PREFIX = 'blft_impression_tracked_'; // test_id + variant_id will be appended
	const BLFT_VIEW_TRACKED_PREFIX = 'blft_view_tracked_'; // test_id + variant_id will be appended
	const BLFT_CONVERSION_TRACKED_SESSION_PREFIX = 'blft_conv_tracked_session_'; // testId_goalType will be appended

	// Object to store flags for conversions tracked in the current page view for certain goal types
	const blftConversionTrackedFlags = {};

	/**
	 * Generates a simple UUID v4.
	 * @returns {string} A UUID string.
	 */
	function generateUUID() {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			const r = (Math.random() * 16) | 0,
				v = c === 'x' ? r : (r & 0x3) | 0x8;
			return v.toString(16);
		});
	}

	/**
	 * Gets or creates a unique visitor hash.
	 * Stores it in localStorage.
	 * @returns {string} The visitor hash.
	 */
	function getOrCreateVisitorHash() {
		let visitorHash = localStorage.getItem(BLFT_VISITOR_HASH_KEY);
		if (!visitorHash) {
			visitorHash = generateUUID();
			localStorage.setItem(BLFT_VISITOR_HASH_KEY, visitorHash);
		}
		return visitorHash;
	}

	/**
	 * Selects a variant for a given test based on distribution percentages.
	 * This is a simplified version. A more robust version would use the visitor hash
	 * to ensure consistent variant assignment for the same user across sessions if not stored.
	 *
	 * @param {string} testId The ID of the test.
	 * @param {Array<Object>} variants Array of variant objects, e.g., [{ id: "var_uuid1", name: "A", distribution: 50 }, ...]
	 * @returns {string|null} The ID of the selected variant, or null if no variants.
	 */
	function chooseVariant(testId, variants) {
		if (!variants || variants.length === 0) {
			return null;
		}

		// Simple weighted random selection
		const randomNumber = Math.random() * 100;
		let cumulativePercentage = 0;

		for (const variant of variants) {
			cumulativePercentage += variant.distribution;
			if (randomNumber < cumulativePercentage) {
				return variant.id;
			}
		}
		// Fallback to the last variant if rounding issues occur, or if distributions don't sum to 100.
		return variants[variants.length - 1].id;
	}


	/**
	 * Initializes all A/B tests found on the page.
	 */
	function initializeABTests() {
		const testContainers = document.querySelectorAll('.blft-test-container[data-blft-test-id]');
		if (testContainers.length === 0) {
			return;
		}

		const visitorHash = getOrCreateVisitorHash();

		testContainers.forEach(container => {
			const testId = container.dataset.blftTestId;
			if (!testId) return;

			// Check test status from localized data
			if (BricksLiftAB_FrontendData && BricksLiftAB_FrontendData.active_tests && BricksLiftAB_FrontendData.active_tests[testId]) {
				const testConfig = BricksLiftAB_FrontendData.active_tests[testId];
				if (testConfig.status === 'completed') {
					// console.log(`BricksLift A/B: Test ${testId} is completed. Skipping variant selection.`);
					// Ensure original content is shown by making sure no variants are explicitly made visible
					// and no blft-variant-hidden is removed from a potential original content wrapper (if applicable).
					// For now, we assume original content shows by default if no variant is activated.
					return; // Skip this test
				}
			}

			const variantWrappers = container.querySelectorAll('.blft-variant-wrapper[data-blft-variant-identifier]');
			if (variantWrappers.length === 0) return;

			// In a real scenario, variant data (IDs, distributions) would be fetched or embedded.
			// For Fáze 3.2, we'll make a placeholder assumption that the `data-blft-variant-identifier`
			// on the wrapper IS the actual variant ID and that we have some way to get distributions.
			// This will be refined when actual variant data from the CPT is integrated.

			// Placeholder: Assume variants are identified by their `data-blft-variant-identifier`
			// and have an equal distribution for now if not otherwise specified.
			// This part needs to be connected to actual test settings (variants and their distributions).
			// For now, we'll just pick one to show.

			let selectedVariantId = localStorage.getItem(BLFT_TEST_VARIANT_PREFIX + testId);

			if (!selectedVariantId) {
				// This is where we'd fetch actual variant data for the testId
				// For now, let's simulate having variant data.
				// This would come from an API call or embedded data in a real implementation.
				const simulatedVariants = Array.from(variantWrappers).map((vw, index) => ({
					id: vw.dataset.blftVariantIdentifier || `simulated-var-${index}`, // Use identifier if present
					name: `Variant ${index + 1}`,
					distribution: 100 / variantWrappers.length // Equal distribution for simulation
				}));

				if (simulatedVariants.length > 0) {
					selectedVariantId = chooseVariant(testId, simulatedVariants);
					if (selectedVariantId) {
						localStorage.setItem(BLFT_TEST_VARIANT_PREFIX + testId, selectedVariantId);
					}
				}
			}
			
			if (selectedVariantId) {
				let variantShown = false;
				variantWrappers.forEach(wrapper => {
					if (wrapper.dataset.blftVariantIdentifier === selectedVariantId) {
						wrapper.classList.remove('blft-variant-hidden');
						wrapper.classList.add('blft-variant-visible');
						variantShown = true;
						trackImpression(testId, selectedVariantId, visitorHash);
						sendTrackViewRequest(testId, selectedVariantId);
					} else {
						wrapper.classList.remove('blft-variant-visible');
						wrapper.classList.add('blft-variant-hidden'); // Ensure others are hidden
					}
				});
				// Fallback: if stored variant ID doesn't match any current variant, show the first one.
				if (!variantShown && variantWrappers.length > 0) {
			                 const firstVariantWrapper = variantWrappers[0];
			                 firstVariantWrapper.classList.remove('blft-variant-hidden');
			                 firstVariantWrapper.classList.add('blft-variant-visible');
			                 // Update localStorage if we had to fallback
			                 const fallbackVariantId = firstVariantWrapper.dataset.blftVariantIdentifier;
			                 if (fallbackVariantId) {
			                     localStorage.setItem(BLFT_TEST_VARIANT_PREFIX + testId, fallbackVariantId);
			                     trackImpression(testId, fallbackVariantId, visitorHash);
			                     sendTrackViewRequest(testId, fallbackVariantId);
			                    }
			                   }

			} else if (variantWrappers.length > 0) {
			 // Fallback: If no variant could be selected (e.g., no variants defined, error), show the first one.
			 const firstVariantWrapper = variantWrappers[0];
			 firstVariantWrapper.classList.remove('blft-variant-hidden');
			 firstVariantWrapper.classList.add('blft-variant-visible');
			 const fallbackVariantId = firstVariantWrapper.dataset.blftVariantIdentifier;
			             if (fallbackVariantId) {
			                  localStorage.setItem(BLFT_TEST_VARIANT_PREFIX + testId, fallbackVariantId); // Store the fallback
			                  trackImpression(testId, fallbackVariantId, visitorHash);
			                  sendTrackViewRequest(testId, fallbackVariantId);
			                }
			               }
                 });
 }

 /**
  * Tracks an impression for a given test and variant.
  * Ensures impression is tracked only once per session for a specific test variant.
  * @param {string} testId
  * @param {string} variantId
  * @param {string} visitorHash
  */
 function trackImpression(testId, variantId, visitorHash) {
  if (!BricksLiftAB_FrontendData || !BricksLiftAB_FrontendData.nonce || !BricksLiftAB_FrontendData.ajax_url) {
   // console.error('BricksLift A/B FrontendData not available for tracking.');
   return;
  }

  const sessionTrackKey = `${BLFT_IMPRESSION_TRACKED_PREFIX}${testId}_${variantId}`;

  if (sessionStorage.getItem(sessionTrackKey)) {
  	return;
  }
 
  if (!checkGDPRConsent(testId)) {
  	// console.log(`GDPR consent not met for test ${testId}. Impression not tracked.`);
  	return;
  }
 
  const formData = new FormData();
  formData.append('action', 'blft_track_event');
  formData.append('nonce', BricksLiftAB_FrontendData.nonce);
  formData.append('test_id', testId);
  formData.append('variant_id', variantId);
  formData.append('event_type', 'view');
  formData.append('visitor_hash', visitorHash);
  formData.append('page_url', window.location.href);

  fetch(BricksLiftAB_FrontendData.ajax_url, {
   method: 'POST',
   body: formData,
  })
  .then(response => response.json())
  .then(data => {
   if (data.success) {
    sessionStorage.setItem(sessionTrackKey, 'true');
   } else {
    // console.error('Failed to track impression:', data.data ? data.data.message : 'Unknown error');
   }
  })
  .catch(error => {
   // console.error('Error sending impression tracking request:', error);
  });
  }
 
  /**
   * Sends an AJAX request to track a variant view and set a cookie.
   * Ensures the request is sent only once per session for a specific test variant.
   * @param {string} testId
   * @param {string} variantId
   */
  function sendTrackViewRequest(testId, variantId) {
  	if (!BricksLiftAB_FrontendData || !BricksLiftAB_FrontendData.track_view_nonce || !BricksLiftAB_FrontendData.ajax_url) {
  		// console.error('BricksLift A/B FrontendData not available for view tracking.');
  		return;
  	}
 
  	const sessionTrackKey = `${BLFT_VIEW_TRACKED_PREFIX}${testId}_${variantId}`;
 
  	if (sessionStorage.getItem(sessionTrackKey)) {
  		// console.log(`View for test ${testId}, variant ${variantId} already tracked this session.`);
  		return;
  	}
 
  	if (!checkGDPRConsent(testId)) {
  		// console.log(`GDPR consent not met for test ${testId}. View not tracked.`);
  		return;
  	}
 
  	const formData = new FormData();
  	formData.append('action', 'blft_track_view');
  	formData.append('nonce', BricksLiftAB_FrontendData.track_view_nonce);
  	formData.append('test_id', testId);
  	formData.append('variant_id', variantId);
 
  	fetch(BricksLiftAB_FrontendData.ajax_url, {
  		method: 'POST',
  		body: formData,
  	})
  	.then(response => response.json())
  	.then(data => {
  		if (data.success) {
  			sessionStorage.setItem(sessionTrackKey, 'true');
  			// console.log(`View tracked for test ${testId}, variant ${variantId}. Cookie attempt: ${data.data.cookie_set}`);
  		} else {
  			// console.error('Failed to track view:', data.data ? data.data.message : 'Unknown error');
  		}
  	})
  	.catch(error => {
  		// console.error('Error sending view tracking request:', error);
  	});
  }
 
  /**
  * Checks GDPR consent for a specific test.
  * @param {string} testId The ID of the test.
  * @returns {boolean} True if consent is met or not required, false otherwise.
  */
 function checkGDPRConsent(testId) {
 	if (!BricksLiftAB_FrontendData || !BricksLiftAB_FrontendData.active_tests || !BricksLiftAB_FrontendData.active_tests[testId]) {
 		// console.warn(`GDPR settings not found for test ${testId}. Assuming consent not required.`);
 		return true; // Default to true if settings are missing, to not break tracking entirely. Consider a stricter default.
 	}

 	const testSettings = BricksLiftAB_FrontendData.active_tests[testId];
 	const gdpr = testSettings.gdpr_settings;

 	if (!gdpr || !gdpr.consent_required) {
 		return true; // Consent not required for this test
 	}

 	if (gdpr.consent_mechanism === 'cookie_key') {
 		if (!gdpr.consent_key_name) {
 			// console.warn(`GDPR consent_key_name not configured for test ${testId}, but consent is required.`);
 			return false; // Cannot check consent if key name is missing
 		}
 		const cookies = document.cookie.split(';');
 		for (let i = 0; i < cookies.length; i++) {
 			let cookie = cookies[i].trim();
 			// Does this cookie string begin with the name we want?
 			if (cookie.startsWith(gdpr.consent_key_name + '=')) {
 				if (!gdpr.consent_key_value) {
 					return true; // Key exists, and no specific value required
 				}
 				const cookieValue = cookie.substring(gdpr.consent_key_name.length + 1);
 				if (cookieValue === gdpr.consent_key_value) {
 					return true; // Key exists and value matches
 				}
 			}
 		}
 		return false; // Cookie key not found or value doesn't match
 	}

 	// Add other mechanisms here if implemented (e.g., 'js_variable')

 	// Default to false if consent is required but mechanism is 'none' or unhandled.
 	// 'none' implies an external system handles consent, and this plugin shouldn't track if consent_required is true.
 	return false;
 	 }
 
 	/**
 	 * Matches a URL based on a specific match type.
 	 * @param {string} currentUrl The current page URL.
 	 * @param {string} targetUrl The target URL to match against.
 	 * @param {string} matchType Type of matching: 'exact', 'contains', 'startsWith', 'endsWith', 'regex'.
 	 * @returns {boolean} True if the URL matches, false otherwise.
 	 */
 	function matchUrl(currentUrl, targetUrl, matchType) {
 		if (!targetUrl || !currentUrl) return false;
 		// Ensure targetUrl for regex is a string, not an object if accidentally passed.
 		const safeTargetUrl = (typeof targetUrl === 'string') ? targetUrl : String(targetUrl);
 
 		try {
 			switch (matchType) {
 				case 'exact':
 					return currentUrl === safeTargetUrl;
 				case 'contains':
 					return currentUrl.includes(safeTargetUrl);
 				case 'startsWith':
 					return currentUrl.startsWith(safeTargetUrl);
 				case 'endsWith':
 					return currentUrl.endsWith(safeTargetUrl);
 				case 'regex':
 					const regex = new RegExp(safeTargetUrl);
 					return regex.test(currentUrl);
 				default:
 					// console.warn(`Unknown URL match type: ${matchType}. Defaulting to exact match.`);
 					return currentUrl === safeTargetUrl;
 			}
 		} catch (e) {
 			// console.error('Error in URL matching:', e);
 			return false;
 		}
 	}
 
 
 	/**
 	 * Tracks a conversion event.
 	 * @param {string} testId The ID of the test.
 	 * @param {string} variantId The ID of the variant.
 	 * @param {string} visitorHash The unique visitor hash.
 	 * @param {string} goalType The type of goal achieved (e.g., 'page_visit', 'selector_click').
 	 * @param {object} goalDetails Specific details about the goal (e.g., URL, selector).
 	 */
 	function trackConversionEvent(testId, variantId, visitorHash, goalType, goalDetails) {
 		if (!BricksLiftAB_FrontendData || !BricksLiftAB_FrontendData.nonce || !BricksLiftAB_FrontendData.ajax_url) {
 			// console.error('BricksLift A/B FrontendData not available for conversion tracking.');
 			return;
 		}
 
 		if (!checkGDPRConsent(testId)) {
 			// console.log(`GDPR consent not met for test ${testId}. Conversion for ${goalType} not tracked.`);
 			return;
 		}
 
 		const formData = new FormData();
 		formData.append('action', 'blft_track_event');
 		formData.append('nonce', BricksLiftAB_FrontendData.nonce);
 		formData.append('test_id', testId);
 		formData.append('variant_id', variantId);
 		formData.append('event_type', 'conversion');
 		formData.append('visitor_hash', visitorHash);
 		formData.append('page_url', window.location.href);
 		formData.append('goal_type', goalType);
 		formData.append('goal_details', JSON.stringify(goalDetails || {}));
 
 		fetch(BricksLiftAB_FrontendData.ajax_url, {
 			method: 'POST',
 			body: formData,
 		})
 		.then(response => response.json())
 		.then(data => {
 			if (data.success) {
 				// console.log(`Conversion tracked for test ${testId}, variant ${variantId}, goal ${goalType}:`, goalDetails);
 			} else {
 				// console.error(`Failed to track conversion for ${testId}, goal ${goalType}:`, data.data ? data.data.message : 'Unknown error');
 			}
 		})
 		.catch(error => {
 			// console.error(`Error sending conversion tracking request for ${testId}, goal ${goalType}:`, error);
 		});
 	}
 
 	/**
 	 * Initializes conversion tracking for all active A/B tests.
 	 */
 	function initializeConversionTracking() {
 		if (!BricksLiftAB_FrontendData || !BricksLiftAB_FrontendData.active_tests) {
 			// console.log('BricksLift A/B: No active tests found for conversion tracking setup.');
 			return;
 		}
 
 		const visitorHash = getOrCreateVisitorHash();
 		const activeTests = BricksLiftAB_FrontendData.active_tests;
 
 		Object.keys(activeTests).forEach(testId => {
 			const testConfig = activeTests[testId];
 			if (!testConfig || !testConfig.goal_config || !testConfig.goal_config.goal_type) {
 				// console.warn(`BricksLift A/B: Goal configuration missing for test ${testId}.`);
 				return;
 			}
 
 			// Ensure the test is not completed
 			if (testConfig.status === 'completed') {
 			    // console.log(`BricksLift A/B: Test ${testId} is completed. Skipping conversion tracking setup.`);
 			    return; // Skip conversion tracking for this completed test
 			}
 
 
 			const selectedVariantId = localStorage.getItem(BLFT_TEST_VARIANT_PREFIX + testId);
 			if (!selectedVariantId) {
 				// console.warn(`BricksLift A/B: No selected variant found for test ${testId} for conversion tracking.`);
 				return; // Cannot track conversion without knowing the variant
 			}
 
 			const goalConfig = testConfig.goal_config;
 			const goalType = goalConfig.goal_type;
 			const trackingFlagKey = `${BLFT_CONVERSION_TRACKED_SESSION_PREFIX}${testId}_${goalType}`;
 
 			// Skip if this specific goal type for this test has already been tracked in this session/page view for certain types
 			if (['time_on_page', 'scroll_depth'].includes(goalType) && sessionStorage.getItem(trackingFlagKey)) {
 				return;
 			}
 
 			switch (goalType) {
 				case 'page_visit':
 					if (goalConfig.goal_pv_url) {
 						if (matchUrl(window.location.href, goalConfig.goal_pv_url, goalConfig.goal_pv_url_match_type)) {
 							if (!sessionStorage.getItem(trackingFlagKey)) {
 								trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 									visited_url: window.location.href,
 									target_url: goalConfig.goal_pv_url,
 									match_type: goalConfig.goal_pv_url_match_type
 								});
 								sessionStorage.setItem(trackingFlagKey, 'true');
 							}
 						}
 					}
 					break;
 
 				case 'selector_click':
 					if (goalConfig.goal_sc_element_selector) {
 						document.querySelectorAll(goalConfig.goal_sc_element_selector).forEach(element => {
 							element.addEventListener('click', () => {
 								// For clicks, we might allow multiple conversions unless specified otherwise by test settings.
 								// For now, let's track each click.
 								trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 									selector: goalConfig.goal_sc_element_selector,
 									clicked_element_tag: element.tagName,
 									clicked_element_id: element.id,
 									clicked_element_classes: element.className
 								});
 							});
 						});
 					}
 					break;
 
 				case 'form_submission':
 					if (goalConfig.goal_fs_form_selector) {
 						document.querySelectorAll(goalConfig.goal_fs_form_selector).forEach(form => {
 							form.addEventListener('submit', () => {
 								// Track on submit attempt. Success should ideally be confirmed by backend or a thank you page.
 								// For now, we track the submission event itself.
 								trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 									form_selector: goalConfig.goal_fs_form_selector,
 									form_id: form.id,
 									form_name: form.name
 								});
 							});
 						});
 					}
 					break;
 
 				case 'time_on_page':
 					if (goalConfig.goal_top_seconds && parseInt(goalConfig.goal_top_seconds, 10) > 0) {
 						setTimeout(() => {
 							if (!sessionStorage.getItem(trackingFlagKey)) {
 								trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 									time_spent_seconds: goalConfig.goal_top_seconds
 								});
 								sessionStorage.setItem(trackingFlagKey, 'true');
 							}
 						}, parseInt(goalConfig.goal_top_seconds, 10) * 1000);
 					}
 					break;
 
 				case 'scroll_depth':
 					if (goalConfig.goal_sd_percentage && parseInt(goalConfig.goal_sd_percentage, 10) > 0) {
 						let scrollDebounceTimer;
 						const handleScroll = () => {
 							clearTimeout(scrollDebounceTimer);
 							if (sessionStorage.getItem(trackingFlagKey)) {
 								window.removeEventListener('scroll', handleScroll);
 								return;
 							}
 							scrollDebounceTimer = setTimeout(() => {
 								const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
 								if (scrollableHeight <= 0) { // Page not scrollable or fully visible
 									if (!sessionStorage.getItem(trackingFlagKey)) {
 										trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 											target_depth_percentage: goalConfig.goal_sd_percentage,
 											achieved_depth_percentage: 100 // Consider it 100% if not scrollable
 										});
 										sessionStorage.setItem(trackingFlagKey, 'true');
 										window.removeEventListener('scroll', handleScroll);
 									}
 									return;
 								}
 								const currentScrollDepth = (window.scrollY / scrollableHeight) * 100;
 								if (currentScrollDepth >= parseInt(goalConfig.goal_sd_percentage, 10)) {
 									if (!sessionStorage.getItem(trackingFlagKey)) {
 										trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 											target_depth_percentage: goalConfig.goal_sd_percentage,
 											achieved_depth_percentage: Math.round(currentScrollDepth)
 										});
 										sessionStorage.setItem(trackingFlagKey, 'true');
 										window.removeEventListener('scroll', handleScroll); // Stop listening after tracking
 									}
 								}
 							}, 100); // Debounce scroll checks
 						};
 						window.addEventListener('scroll', handleScroll, { passive: true });
 						// Initial check in case the depth is already met on page load
 						handleScroll();
 					}
 					break;
 
 				case 'custom_js_event':
 					if (goalConfig.goal_cje_event_name) {
 						document.addEventListener(goalConfig.goal_cje_event_name, (event) => {
 							trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 								event_name: goalConfig.goal_cje_event_name,
 								event_detail: event.detail || null
 							});
 						});
 					}
 					break;
 				
 				case 'wc_add_to_cart':
 					// This relies on WooCommerce (or another plugin) firing a 'wc_added_to_cart' custom event on the document body.
 					// This is a common pattern but might need adjustment based on actual WC setup.
 					// jQuery event: jQuery(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) { ... });
 					// We listen for a potential vanilla JS equivalent.
 					document.body.addEventListener('wc_added_to_cart', (event) => {
 						// Extract product ID if available from event.detail
 						let productId = null;
 						if (event.detail && event.detail.product_id) {
 							productId = event.detail.product_id;
 						} else if (event.detail && event.detail.target && event.detail.target.dataset && event.detail.target.dataset.productId) {
 							// Fallback if event.target is the button and has data-product-id
 							productId = event.detail.target.dataset.productId;
 						}
 						
 						const trackThisConversion = goalConfig.goal_wc_any_product ||
 													(productId && goalConfig.goal_wc_product_id && parseInt(productId, 10) === parseInt(goalConfig.goal_wc_product_id, 10));
 
 						if (trackThisConversion) {
 							trackConversionEvent(testId, selectedVariantId, visitorHash, goalType, {
 								any_product: goalConfig.goal_wc_any_product,
 								product_id: productId, // Send what we found
 								target_product_id: goalConfig.goal_wc_product_id,
 								event_detail: event.detail || null
 							});
 						}
 					});
 					break;
 
 				default:
 					// console.warn(`BricksLift A/B: Unknown goal type '${goalType}' for test ${testId}.`);
 			}
 		});
 	}
 
 
 	 /**
 	  * DOMContentLoaded listener to initialize tests and conversion tracking.
 	  */
 	 function onDomReady() {
 	initializeABTests();
 	initializeConversionTracking();
 	 }
 
 	 if (document.readyState === 'loading') {
 	 	document.addEventListener('DOMContentLoaded', onDomReady);
 	 } else {
 	 	onDomReady();
 	 }
 
 })();