import * as React from 'react';
import { Search, Filter, Download, Upload } from 'lucide-react';
import { Input } from '../ui/Input';
import { Button } from '../ui/Button';

function UserFilters({
  searchQuery,
  setSearchQuery,
  activeFilter,
  setActiveFilter,
}) {
  return (
    <div className="flex items-center justify-between">
      <div className="relative w-1/3">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search className="h-4 w-4 text-blue-400" />
        </div>
        <Input
          type="text"
          className="bg-black border border-blue-500/50 text-blue-100 rounded-md pl-10 pr-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-400 font-mono placeholder-blue-300/50"
          placeholder="Search astronauts..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
        />
      </div>
      <div className="flex items-center gap-2">
        <div className="bg-black border border-blue-500/50 rounded-md p-1 flex">
          <button
            className={`px-3 py-1.5 text-sm rounded font-mono ${
              activeFilter === 'all'
                ? 'bg-blue-600 text-white'
                : 'text-blue-400 hover:text-blue-300 hover:bg-blue-500/10'
            } transition-colors`}
            onClick={() => setActiveFilter('all')}
          >
            ALL USERS
          </button>
          <button
            className={`px-3 py-1.5 text-sm rounded font-mono ${
              activeFilter === 'active'
                ? 'bg-blue-600 text-white'
                : 'text-blue-400 hover:text-blue-300 hover:bg-blue-500/10'
            } transition-colors`}
            onClick={() => setActiveFilter('active')}
          >
            ACTIVE
          </button>
          <button
            className={`px-3 py-1.5 text-sm rounded font-mono ${
              activeFilter === 'inactive'
                ? 'bg-blue-600 text-white'
                : 'text-blue-400 hover:text-blue-300 hover:bg-blue-500/10'
            } transition-colors`}
            onClick={() => setActiveFilter('inactive')}
          >
            INACTIVE
          </button>
        </div>
        <Button
          variant="outline"
          className="bg-black border border-blue-500/50 hover:bg-blue-500/10 text-blue-400 p-2"
        >
          <Filter className="h-5 w-5" />
        </Button>
        <Button
          variant="outline"
          className="bg-black border border-blue-500/50 hover:bg-blue-500/10 text-blue-400 p-2"
        >
          <Download className="h-5 w-5" />
        </Button>
        <Button
          variant="outline"
          className="bg-black border border-blue-500/50 hover:bg-blue-500/10 text-blue-400 p-2"
        >
          <Upload className="h-5 w-5" />
        </Button>
      </div>
    </div>
  );
}

export default UserFilters;
