import * as React from "react";
import { Search, ArrowUpDown } from "lucide-react";
import { Button } from "../ui/Button";

function InventoryFilters({
  searchQuery,
  setSearchQuery,
  sortField,
  setSortField,
  sortDirection,
  setSortDirection,
}) {
  const handleSort = (field) => {
    if (sortField === field) {
      // Toggle direction if same field
      setSortDirection(sortDirection === "asc" ? "desc" : "asc");
    } else {
      // Set new field and default to ascending
      setSortField(field);
      setSortDirection("asc");
    }
  };

  return (
    <div className="flex flex-col md:flex-row gap-3 mb-4">
      <div className="relative flex-grow">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search className="h-4 w-4 text-neutral-500" />
        </div>
        <input
          type="text"
          placeholder="Search inventory..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full pl-10 pr-4 py-2 bg-neutral-900 border border-neutral-800 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <div className="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          className={`text-xs ${
            sortField === "name"
              ? "bg-blue-900/20 border-blue-800 text-white"
              : "bg-neutral-900 border-neutral-800 text-white"
          }`}
          onClick={() => handleSort("name")}
        >
          Name
          {sortField === "name" && (
            <ArrowUpDown className="ml-1 h-3 w-3 text-neutral-400" />
          )}
        </Button>

        <Button
          variant="outline"
          size="sm"
          className={`text-xs ${
            sortField === "quantity"
              ? "bg-blue-900/20 border-blue-800 text-white dark:bg-blue-900/20 dark:border-blue-800 dark:text-white"
              : "bg-neutral-900 border-neutral-800 text-white dark:bg-neutral-900 dark:border-neutral-800 dark:text-white"
          }`}
          onClick={() => handleSort("quantity")}
        >
          Quantity
          {sortField === "quantity" && (
            <ArrowUpDown className="ml-1 h-3 w-3 text-neutral-400 dark:text-neutral-400" />
          )}
        </Button>

        <Button
          variant="outline"
          size="sm"
          className={`text-xs ${
            sortField === "category"
              ? "bg-blue-900/20 border-blue-800 text-white dark:bg-blue-900/20 dark:border-blue-800 dark:text-white"
              : "bg-neutral-900 border-neutral-800 text-white dark:bg-neutral-900 dark:border-neutral-800 dark:text-white"
          }`}
          onClick={() => handleSort("category")}
        >
          Category
          {sortField === "category" && (
            <ArrowUpDown className="ml-1 h-3 w-3 text-neutral-400 dark:text-neutral-400" />
          )}
        </Button>
      </div>
    </div>
  );
}

export default InventoryFilters;