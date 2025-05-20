import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import create from 'zustand';
import TestListFilters from './TestListFilters';
import CreateTestButton from './CreateTestButton';
import TestListTable from './TestListTable';
import PaginationControls from './PaginationControls';

// Placeholder for API functions - replace with actual API calls
const api = {
    fetchTests: async ({ status, orderby, order, page }) => {
        // Simulate API call
        console.log('Fetching tests with params:', { status, orderby, order, page });
        const perPage = 10; // Assuming 10 items per page
        // Construct query parameters
        const queryParams = new URLSearchParams({
            status: status === 'all' ? '' : status, // API might expect empty for 'all'
            orderby,
            order,
            page,
            per_page: perPage,
        });

        // Replace with your actual API endpoint
        const response = await fetch(`/wp-json/blft/v1/tests?${queryParams.toString()}`);
        if (!response.ok) {
            throw new Error(__('Failed to fetch tests', 'brickslift-ab-testing'));
        }
        const totalCount = parseInt(response.headers.get('X-WP-Total') || '0', 10);
        const totalPages = parseInt(response.headers.get('X-WP-TotalPages') || '0', 10);
        const tests = await response.json();
        return { tests, totalCount, totalPages };
    }
};


// Zustand store for tests
const useTestsStore = create((set, get) => ({
    tests: [],
    filters: {
        status: 'all', // Default filter
    },
    sorting: {
        orderby: 'title', // Default sort
        order: 'asc',
    },
    pagination: {
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
    },
    isLoading: false,
    error: null,

    fetchTests: async () => {
        set({ isLoading: true, error: null });
        try {
            const { filters, sorting, pagination } = get();
            const { tests, totalCount, totalPages } = await api.fetchTests({
                status: filters.status,
                orderby: sorting.orderby,
                order: sorting.order,
                page: pagination.currentPage,
            });
            set({
                tests,
                pagination: { ...pagination, totalItems: totalCount, totalPages },
                isLoading: false,
            });
        } catch (error) {
            console.error("Error fetching tests:", error);
            set({ error: error.message, isLoading: false });
        }
    },

    setFilters: (newFilters) => {
        set((state) => ({
            filters: { ...state.filters, ...newFilters },
            pagination: { ...state.pagination, currentPage: 1 }, // Reset to first page on filter change
        }));
        get().fetchTests(); // Refetch tests with new filters
    },

    setSorting: (orderby) => {
        set((state) => {
            const newOrder = state.sorting.orderby === orderby && state.sorting.order === 'asc' ? 'desc' : 'asc';
            return {
                sorting: { orderby, order: newOrder },
                pagination: { ...state.pagination, currentPage: 1 }, // Reset to first page on sort change
            };
        });
        get().fetchTests(); // Refetch tests with new sorting
    },

    setCurrentPage: (page) => {
        set((state) => ({
            pagination: { ...state.pagination, currentPage: page },
        }));
        get().fetchTests(); // Refetch tests for the new page
    },
}));

const TestListContainer = ({ onNavigateToEditor, onNavigateToDetail }) => { // Added onNavigateToDetail
    const {
        tests,
        filters,
        sorting,
        pagination,
        isLoading,
        error,
        fetchTests,
        setFilters,
        setSorting,
        setCurrentPage,
    } = useTestsStore();

    useEffect(() => {
        fetchTests();
    }, []); // Initial fetch

    if (isLoading && tests.length === 0) {
        return <p>{__('Loading tests...', 'brickslift-ab-testing')}</p>;
    }

    if (error) {
        return <p>{__('Error loading tests:', 'brickslift-ab-testing')} {error}</p>;
    }

    return (
        <div className="test-list-container">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h2>{__('A/B Tests', 'brickslift-ab-testing')}</h2>
                <CreateTestButton onClick={() => onNavigateToEditor()} />
            </div>
            <TestListFilters
                filters={filters}
                onFilterChange={setFilters}
            />
            {isLoading && tests.length === 0 && <p>{__('Loading tests...', 'brickslift-ab-testing')}</p>}
            {!isLoading && error && <p>{__('Error loading tests:', 'brickslift-ab-testing')} {error}</p>}
            {!isLoading && !error && tests.length === 0 && <p>{__('No tests found.', 'brickslift-ab-testing')}</p>}

            {tests.length > 0 &&
                <TestListTable
                    tests={tests}
                    sorting={sorting}
                    onSort={setSorting}
                    onEditTest={onNavigateToEditor}
                    onViewDetails={onNavigateToDetail} // Pass the new navigation function for details
                />
            }
            {isLoading && tests.length > 0 && <p>{__('Updating list...', 'brickslift-ab-testing')}</p>}
            <PaginationControls
                currentPage={pagination.currentPage}
                totalPages={pagination.totalPages}
                onPageChange={setCurrentPage}
            />
        </div>
    );
};

export default TestListContainer;
export { useTestsStore }; // Export store for potential use in other components or for testing