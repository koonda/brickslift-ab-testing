import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const PaginationControls = ({ currentPage, totalPages, onPageChange }) => {
    if (totalPages <= 1) {
        return null; // Don't show pagination if there's only one page or less
    }

    const handlePrevious = () => {
        if (currentPage > 1) {
            onPageChange(currentPage - 1);
        }
    };

    const handleNext = () => {
        if (currentPage < totalPages) {
            onPageChange(currentPage + 1);
        }
    };

    // Basic page numbers display (e.g., 1 ... 5 6 7 ... 10)
    // This can be made more sophisticated.
    const pageNumbers = [];
    const maxPagesToShow = 5; // Max number of page links to show around current page
    const halfMaxPages = Math.floor(maxPagesToShow / 2);

    if (totalPages <= maxPagesToShow + 2) { // Show all pages if not too many
        for (let i = 1; i <= totalPages; i++) {
            pageNumbers.push(i);
        }
    } else {
        pageNumbers.push(1); // Always show first page
        if (currentPage > halfMaxPages + 2) {
            pageNumbers.push('...'); // Ellipsis if far from start
        }

        let startPage = Math.max(2, currentPage - halfMaxPages);
        let endPage = Math.min(totalPages - 1, currentPage + halfMaxPages);

        if (currentPage <= halfMaxPages + 1) {
            endPage = Math.min(totalPages -1, maxPagesToShow);
        }
        if (currentPage >= totalPages - halfMaxPages) {
            startPage = Math.max(2, totalPages - maxPagesToShow +1);
        }


        for (let i = startPage; i <= endPage; i++) {
            pageNumbers.push(i);
        }

        if (currentPage < totalPages - halfMaxPages - 1) {
            pageNumbers.push('...'); // Ellipsis if far from end
        }
        pageNumbers.push(totalPages); // Always show last page
    }


    return (
        <div className="test-list-pagination-controls" style={{ marginTop: '20px', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '10px' }}>
            <Button
                onClick={handlePrevious}
                disabled={currentPage === 1}
                isSmall
            >
                {__('Previous', 'brickslift-ab-testing')}
            </Button>

            {pageNumbers.map((page, index) =>
                typeof page === 'number' ? (
                    <Button
                        key={index}
                        isSmall
                        isPrimary={currentPage === page}
                        variant={currentPage === page ? undefined : "tertiary"}
                        onClick={() => onPageChange(page)}
                        disabled={currentPage === page}
                    >
                        {page}
                    </Button>
                ) : (
                    <span key={index} style={{ padding: '0 5px' }}>{page}</span>
                )
            )}

            <Button
                onClick={handleNext}
                disabled={currentPage === totalPages}
                isSmall
            >
                {__('Next', 'brickslift-ab-testing')}
            </Button>
        </div>
    );
};

export default PaginationControls;