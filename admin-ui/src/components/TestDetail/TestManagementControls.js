import React, { useState } from 'react';
// Assuming api utility is in admin-ui/src/utils/api.js
// import { apiPost } from '../../utils/api'; 
// Assuming a toast notification system is in place
// import { toast } from 'react-toastify'; // or any other toast library

// Placeholder for actual API calls and store updates
const apiPost = async (url, data) => {
    console.log(`Mock API POST: ${url}`, data);
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500));
    // Simulate a successful response, actual API would return data
    return { success: true, message: 'Action successful', data: {} }; 
};

const toast = {
    success: (message) => console.log(`Toast Success: ${message}`),
    error: (message) => console.log(`Toast Error: ${message}`),
};

const TestManagementControls = ({ test, testDetailStore, testsStore }) => {
    const [selectedWinnerVariantId, setSelectedWinnerVariantId] = useState('');

    if (!test) {
        return <div>Loading test details...</div>;
    }

    const { id, status } = test;

    const handleAction = async (action, additionalData = {}) => {
        try {
            const payload = { action, ...additionalData };
            const response = await apiPost(`/wp-json/blft/v1/tests/${id}/status`, payload);
            if (response.success) {
                toast.success(response.message || `${action.replace('_', ' ')} successful!`);
                // Refresh test data
                if (testDetailStore && typeof testDetailStore.fetchTestDetails === 'function') {
                    testDetailStore.fetchTestDetails(id);
                }
                // Potentially refresh list if status change affects it
                if (testsStore && typeof testsStore.fetchTests === 'function') {
                    testsStore.fetchTests(); 
                }
                if (action === 'declare_winner') {
                    setSelectedWinnerVariantId(''); // Reset after declaring
                }
            } else {
                toast.error(response.message || `Failed to ${action.replace('_', ' ')}.`);
            }
        } catch (error) {
            console.error(`Error performing action ${action}:`, error);
            toast.error(`An error occurred while trying to ${action.replace('_', ' ')}.`);
        }
    };

    const handleDuplicate = async () => {
        try {
            const response = await apiPost(`/wp-json/blft/v1/tests/${id}/duplicate`, {});
            if (response.success && response.data && response.data.new_test_id) {
                toast.success('Test duplicated successfully!');
                // TODO: Navigate to the edit screen for the new duplicated test
                // e.g., history.push(`/tests/${response.data.new_test_id}/edit`);
                console.log(`Navigate to edit screen for new test ID: ${response.data.new_test_id}`);
                 if (testsStore && typeof testsStore.fetchTests === 'function') {
                    testsStore.fetchTests(); 
                }
            } else {
                toast.error(response.message || 'Failed to duplicate test.');
            }
        } catch (error) {
            console.error('Error duplicating test:', error);
            toast.error('An error occurred while duplicating the test.');
        }
    };
    
    const handleDeclareWinnerClick = () => {
        if (!selectedWinnerVariantId) {
            toast.error('Please select a winning variant.');
            return;
        }
        handleAction('declare_winner', { winner_variant_id: selectedWinnerVariantId });
    };


    return (
        <div className="test-management-controls">
            <h3>Test Management</h3>
            <div className="controls-buttons">
                {(status === 'draft' || status === 'paused') && (
                    <button onClick={() => handleAction('start')} className="button button-primary">
                        Start Test
                    </button>
                )}
                {status === 'running' && (
                    <button onClick={() => handleAction('pause')} className="button">
                        Pause Test
                    </button>
                )}
                {(status === 'running' || status === 'paused') && (
                    <button onClick={() => handleAction('stop')} className="button button-secondary">
                        Stop Test
                    </button>
                )}
                {(status === 'completed' || status === 'draft') && (
                    <button onClick={() => handleAction('archive')} className="button">
                        Archive Test
                    </button>
                )}
                
                {(status === 'running' || status === 'completed') /* && !test.winner_variant_id */ && (
                     <div style={{ marginTop: '10px', borderTop: '1px solid #eee', paddingTop: '10px' }}>
                        <h4>Declare Winner</h4>
                        <select 
                            value={selectedWinnerVariantId} 
                            onChange={(e) => setSelectedWinnerVariantId(e.target.value)}
                            style={{ marginRight: '10px' }}
                        >
                            <option value="" disabled>Select Winning Variant</option>
                            {test.variations && test.variations.map(v => (
                                <option key={v.id} value={v.id}>{v.name || `Variant ${v.id}`}</option>
                            ))}
                        </select>
                        <button 
                            onClick={handleDeclareWinnerClick}
                            className="button"
                            disabled={!selectedWinnerVariantId}
                        >
                            Declare Winner
                        </button>
                    </div>
                )}

                <button onClick={handleDuplicate} className="button" style={{ marginTop: '10px' }}>
                    Duplicate Test
                </button>
            </div>
        </div>
    );
};

export default TestManagementControls;