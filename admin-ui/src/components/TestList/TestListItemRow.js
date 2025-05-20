import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const TestListItemRow = ({ test, onViewDetailsClick, onEditTestClick }) => {
    const {
        id,
        title,
        status,
        start_date,
        end_date,
        variations_count,
        goal_type,
        winner_variant_id
    } = test;

    const getStatusLabel = (statusValue) => {
        // This could be expanded with i18n and more statuses
        switch (statusValue) {
            case 'draft':
                return __('Draft', 'brickslift-ab-testing');
            case 'running':
                return __('Running', 'brickslift-ab-testing');
            case 'paused':
                return __('Paused', 'brickslift-ab-testing');
            case 'completed':
                return __('Completed', 'brickslift-ab-testing');
            default:
                return statusValue;
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        // Adjust to ensure the date is interpreted as UTC to avoid timezone shifts
        const date = new Date(dateString + 'T00:00:00Z');
        return date.toLocaleDateString(undefined, { timeZone: 'UTC' });
    };


    return (
        <tr>
            <td>{title?.rendered || title || __('N/A', 'brickslift-ab-testing')}</td>
            <td>{getStatusLabel(status)}</td>
            <td>{formatDate(start_date)}</td>
            <td>{formatDate(end_date)}</td>
            <td>{variations_count !== undefined ? variations_count : __('N/A', 'brickslift-ab-testing')}</td>
            <td>{goal_type || __('N/A', 'brickslift-ab-testing')}</td>
            <td>{winner_variant_id || '-'}</td>
            <td className="actions-column">
                <Button
                    isSmall
                    variant="primary" // Changed to primary for edit
                    onClick={() => {
                        if (onEditTestClick) {
                            onEditTestClick(id);
                        }
                    }}
                    style={{ marginRight: '8px' }}
                >
                    {__('Edit', 'brickslift-ab-testing')}
                </Button>
                <Button
                    isSmall
                    variant="secondary" // Keep as secondary, or choose another appropriate variant
                    onClick={() => {
                        // console.log('View Details for test ID (from TestListItemRow):', id); // Keep for debugging if needed
                        if (onViewDetailsClick) {
                            onViewDetailsClick(id); // This will now navigate to the TestDetail view via App.js
                        }
                    }}
                >
                    {__('View Details', 'brickslift-ab-testing')}
                </Button>
            </td>
        </tr>
    );
};

export default TestListItemRow;