import * as React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '../ui/Button';
function UserPagination({
  filteredUsers,
  currentPage,
  setCurrentPage,
  usersPerPage,
}) {
  return (
    <div className="flex items-center justify-between mt-4">
      <div className="text-sm text-blue-300/70 font-mono">
        Showing{' '}
        <span className="font-medium text-blue-300">
          {filteredUsers.length === 0
            ? 0
            : (currentPage - 1) * usersPerPage + 1}
        </span>{' '}
        to{' '}
        <span className="font-medium text-blue-300">
          {Math.min(currentPage * usersPerPage, filteredUsers.length)}
        </span>{' '}
        of{' '}
        <span className="font-medium text-blue-300">
          {filteredUsers.length}
        </span>{' '}
        users
      </div>
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          className="bg-black border border-blue-500/50 hover:bg-blue-500/10 text-blue-400 p-2 disabled:opacity-50 disabled:cursor-not-allowed"
          disabled={currentPage === 1}
          onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
        >
          <ChevronLeft className="h-4 w-4" />
        </Button>
        <div className="flex items-center">
          {(() => {
            const totalPages = Math.max(
              Math.ceil(filteredUsers.length / usersPerPage),
              1,
            );
            let startPage = Math.max(currentPage - 2, 1);
            let endPage = Math.min(startPage + 4, totalPages);
            if (endPage - startPage < 4) {
              startPage = Math.max(endPage - 4, 1);
            }
            return Array.from(
              { length: Math.min(5, endPage - startPage + 1) },
              (_, i) => startPage + i,
            ).map((page) => (
              <Button
                key={page}
                variant={page === currentPage ? 'default' : 'outline'}
                className={`w-8 h-8 flex items-center justify-center rounded-md font-mono text-sm ${
                  page === currentPage
                    ? 'bg-blue-600 text-white'
                    : 'bg-black border border-blue-500/50 hover:bg-blue-500/10 text-blue-400'
                }`}
                onClick={() => setCurrentPage(page)}
              >
                {page}
              </Button>
            ));
          })()}
        </div>
        <Button
          variant="outline"
          className="bg-black border border-blue-500/50 hover:bg-blue-500/10 text-blue-400 p-2 disabled:opacity-50 disabled:cursor-not-allowed"
          disabled={
            currentPage >= Math.ceil(filteredUsers.length / usersPerPage)
          }
          onClick={() =>
            setCurrentPage((prev) =>
              Math.min(
                prev + 1,
                Math.ceil(filteredUsers.length / usersPerPage),
              ),
            )
          }
        >
          <ChevronRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}

export default UserPagination;
