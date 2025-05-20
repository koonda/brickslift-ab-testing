import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl } from '@wordpress/components';
import React from 'react'; // Import React for JSX

const TestListFilters = ({ filters, onFilterChange }) => {
    const statusOptions = [
        { label: __('All Statuses', 'brickslift-ab-testing'), value: 'all' },
        { label: __('Draft', 'brickslift-ab-testing'), value: 'draft' },
        { label: __('Running', 'brickslift-ab-testing'), value: 'running' },
        { label: __('Paused', 'brickslift-ab-testing'), value: 'paused' },
        { label: __('Completed', 'brickslift-ab-testing'), value: 'completed' },
        // Consider adding 'archived' if it's a filterable status
    ];

    const handleFilterChange = (key, value) => {
        onFilterChange({ [key]: value });
    };

    return (
        <div className="test-list-filters" style={{
            marginBottom: '20px',
            display: 'flex',
            flexWrap: 'wrap', // Allow wrapping for smaller screens
            gap: '15px',
            alignItems: 'flex-end' // Align items to the bottom for better label alignment
        }}>
            <SelectControl
                label={__('Status:', 'brickslift-ab-testing')}
                value={filters.status || 'all'}
                options={statusOptions}
                onChange={(newStatus) => handleFilterChange('status', newStatus)}
                __nextHasNoMarginBottom
            />
            <div className="filter-group">
                <label htmlFor="blft-start-date-filter">{__('Start Date:', 'brickslift-ab-testing')}</label>
                <input
                    type="date"
                    id="blft-start-date-filter"
                    value={filters.startDate || ''}
                    onChange={(e) => handleFilterChange('startDate', e.target.value)}
                    style={{ padding: '6px 8px', border: '1px solid #8c8f94', borderRadius: '2px' }}
                />
            </div>
            <div className="filter-group">
                <label htmlFor="blft-end-date-filter">{__('End Date:', 'brickslift-ab-testing')}</label>
                <input
                    type="date"
                    id="blft-end-date-filter"
                    value={filters.endDate || ''}
                    onChange={(e) => handleFilterChange('endDate', e.target.value)}
                    style={{ padding: '6px 8px', border: '1px solid #8c8f94', borderRadius: '2px' }}
                />
            </div>
            <TextControl
                label={__('Search Tests:', 'brickslift-ab-testing')}
                value={filters.searchQuery || ''}
                onChange={(newValue) => handleFilterChange('searchQuery', newValue)}
                placeholder={__('Enter keyword...', 'brickslift-ab-testing')}
                __nextHasNoMarginBottom
            />
        </div>
    );
};

export default TestListFilters;