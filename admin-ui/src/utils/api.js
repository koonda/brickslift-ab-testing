// Assuming wp.apiFetch is available in the WordPress admin environment.
// If not, this would need to be replaced with standard fetch and manual nonce handling.

/**
 * Fetches detailed data for a specific test.
 * @param {number|string} testId The ID of the test.
 * @returns {Promise<Object>} The test data.
 */
export const fetchTestData = async (testId) => {
  if (!testId) {
    throw new Error('Test ID is required to fetch test data.');
  }
  return await window.wp.apiFetch({ path: `/blft/v1/tests/${testId}` });
};

/**
 * Fetches aggregated statistics for a specific test.
 * @param {number|string} testId The ID of the test.
 * @returns {Promise<Object>} The aggregated statistics.
 */
export const fetchAggregatedStats = async (testId) => {
  if (!testId) {
    throw new Error('Test ID is required to fetch aggregated stats.');
  }
  return await window.wp.apiFetch({ path: `/blft/v1/stats?test_id=${testId}` });
};

/**
 * Fetches daily statistics for a specific test.
 * @param {number|string} testId The ID of the test.
 * @returns {Promise<Array<Object>>} An array of daily statistics.
 */
export const fetchDailyStats = async (testId) => {
  if (!testId) {
    throw new Error('Test ID is required to fetch daily stats.');
  }
  return await window.wp.apiFetch({ path: `/blft/v1/test-stats-daily/${testId}` });
};

/**
 * Fetches the list of tests.
 * @param {object} params Query parameters for filtering, pagination, sorting.
 * @returns {Promise<Array<Object>>} An array of tests.
 */
export const fetchTests = async (params = {}) => {
  const query = new URLSearchParams(params).toString();
  return await window.wp.apiFetch({ path: `/blft/v1/tests${query ? `?${query}` : ''}` });
};


/**
 * Creates a new test.
 * @param {Object} testData The data for the new test.
 * @returns {Promise<Object>} The created test data.
 */
export const createTest = async (testData) => {
    return await window.wp.apiFetch({
        path: '/blft/v1/tests',
        method: 'POST',
        data: testData,
    });
};

/**
 * Updates an existing test.
 * @param {number|string} testId The ID of the test to update.
 * @param {Object} testData The data to update the test with.
 * @returns {Promise<Object>} The updated test data.
 */
export const updateTest = async (testId, testData) => {
    if (!testId) {
        throw new Error('Test ID is required to update a test.');
    }
    return await window.wp.apiFetch({
        path: `/blft/v1/tests/${testId}`,
        method: 'PUT',
        data: testData,
    });
};

/**
 * Deletes a test.
 * @param {number|string} testId The ID of the test to delete.
 * @returns {Promise<Object>} Response from the delete operation.
 */
export const deleteTest = async (testId) => {
    if (!testId) {
        throw new Error('Test ID is required to delete a test.');
    }
    return await window.wp.apiFetch({
        path: `/blft/v1/tests/${testId}`,
        method: 'DELETE',
    });
};