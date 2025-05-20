import { __ } from '@wordpress/i18n';

const SortableTableColumnHeader = ({ columnKey, displayName, currentSortKey, currentSortOrder, onSortClick, isSortable = true }) => {
    const handleClick = () => {
        if (isSortable && onSortClick) {
            onSortClick(columnKey);
        }
    };

    let sortIndicator = '';
    if (isSortable && currentSortKey === columnKey) {
        sortIndicator = currentSortOrder === 'asc' ? ' \u2191' : ' \u2193'; // Up arrow or Down arrow
    }

    const thProps = {
        onClick: isSortable ? handleClick : undefined,
        style: isSortable ? { cursor: 'pointer' } : {},
        className: isSortable ? 'sortable' : '',
        scope: 'col',
    };
    if (currentSortKey === columnKey) {
        thProps['aria-sort'] = currentSortOrder === 'asc' ? 'ascending' : 'descending';
    }


    return (
        <th {...thProps}>
            {displayName}
            {sortIndicator}
        </th>
    );
};

export default SortableTableColumnHeader;