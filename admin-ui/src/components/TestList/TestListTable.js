import { __ } from '@wordpress/i18n';
import SortableTableColumnHeader from './SortableTableColumnHeader';
import TestListItemRow from './TestListItemRow';

const TestListTable = ({ tests, sorting, onSort, onViewDetails, onEditTest }) => {
    if (!tests || tests.length === 0) {
        return <p>{__('No tests found.', 'brickslift-ab-testing')}</p>;
    }

    return (
        <table className="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <SortableTableColumnHeader
                        columnKey="title"
                        displayName={__('Test Name', 'brickslift-ab-testing')}
                        currentSortKey={sorting.orderby}
                        currentSortOrder={sorting.order}
                        onSortClick={onSort}
                        isSortable={true}
                    />
                    <SortableTableColumnHeader
                        columnKey="status"
                        displayName={__('Status', 'brickslift-ab-testing')}
                        currentSortKey={sorting.orderby}
                        currentSortOrder={sorting.order}
                        onSortClick={onSort}
                        isSortable={true}
                    />
                    <SortableTableColumnHeader
                        columnKey="start_date"
                        displayName={__('Start Date', 'brickslift-ab-testing')}
                        currentSortKey={sorting.orderby}
                        currentSortOrder={sorting.order}
                        onSortClick={onSort}
                        isSortable={true}
                    />
                     <SortableTableColumnHeader
                        columnKey="end_date"
                        displayName={__('End Date', 'brickslift-ab-testing')}
                        currentSortKey={sorting.orderby}
                        currentSortOrder={sorting.order}
                        onSortClick={onSort}
                        isSortable={true}
                    />
                    <SortableTableColumnHeader
                        columnKey="variations_count"
                        displayName={__('Variations', 'brickslift-ab-testing')}
                        isSortable={false}
                    />
                    <SortableTableColumnHeader
                        columnKey="goal_type"
                        displayName={__('Goal', 'brickslift-ab-testing')}
                        isSortable={false}
                    />
                    <SortableTableColumnHeader
                        columnKey="winner_variant_id"
                        displayName={__('Winner', 'brickslift-ab-testing')}
                        isSortable={false}
                    />
                    <SortableTableColumnHeader
                        columnKey="actions"
                        displayName={__('Actions', 'brickslift-ab-testing')}
                        isSortable={false}
                    />
                </tr>
            </thead>
            <tbody>
                {tests.map((test) => (
                    <TestListItemRow
                        key={test.id}
                        test={test}
                        onViewDetailsClick={onViewDetails}
                        onEditTestClick={onEditTest} // Pass onEditTest to TestListItemRow
                    />
                ))}
            </tbody>
        </table>
    );
};

export default TestListTable;