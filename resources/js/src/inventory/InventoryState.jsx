import * as React from "react";
import { Boxes, PackageCheck, PackageX } from "lucide-react";

function InventoryState({ stats = { total: 0, inStock: 0, lowStock: 0, outOfStock: 0, categories: [] } }) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
      <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <Boxes className="h-4 w-4 text-blue-500 mr-2" />
            <span className="text-xs text-neutral-400">TOTAL ITEMS</span>
          </div>
          <span className="text-sm font-mono text-blue-400">
            {stats.total}
          </span>
        </div>
      </div>

      <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <PackageCheck className="h-4 w-4 text-green-500 mr-2" />
            <span className="text-xs text-neutral-400">IN STOCK</span>
          </div>
          <span className="text-sm font-mono text-green-400">
            {stats.inStock}
          </span>
        </div>
      </div>

      <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <PackageX className="h-4 w-4 text-red-500 mr-2" />
            <span className="text-xs text-neutral-400">OUT OF STOCK</span>
          </div>
          <span className="text-sm font-mono text-red-400">
            {stats.outOfStock}
          </span>
        </div>
      </div>
    </div>
  );
}

export default InventoryState;