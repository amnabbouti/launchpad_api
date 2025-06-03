import * as React from 'react';
import { Eye, Pen, Trash } from 'lucide-react';
import { Button } from '../ui/Button';

function InventoryTable({ items = [] }) {
  if (!items || items.length === 0) {
    return (
      <div className="text-center text-neutral-400 py-4">
        No inventory items found.
      </div>
    );
  }

  return (
    <div className="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="text-xs font-mono text-neutral-400 bg-neutral-800/60">
              <th className="px-4 py-2 text-left">NAME</th>
              <th className="px-4 py-2 text-left">CODE</th>
              <th className="px-4 py-2 text-left">CATEGORY</th>
              <th className="px-4 py-2 text-center">QUANTITY</th>
              <th className="px-4 py-2 text-left">LOCATION</th>
              <th className="px-4 py-2 text-right">ACTIONS</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-neutral-800">
            {items.map((item) => (
              <tr key={item.id} className="text-sm">
                <td className="px-4 py-3">
                  <div className="font-medium">{item.name}</div>
                  <div className="text-xs text-neutral-500 mt-0.5 max-w-xs truncate">
                    {item.description}
                  </div>
                </td>
                <td className="px-4 py-3 font-mono text-xs">{item.code}</td>
                <td className="px-4 py-3">
                  <span className="inline-flex items-center px-2 py-0.5 rounded text-xs bg-neutral-800 text-neutral-300">
                    {item.category_name || 'Uncategorized'}
                  </span>
                </td>
                <td className="px-4 py-3 text-center">
                  <span
                    className={`font-mono ${
                      item.quantity === 0
                        ? 'text-red-400'
                        : item.quantity < 5
                        ? 'text-amber-400'
                        : 'text-green-400'
                    }`}
                  >
                    {item.quantity} {item.unit}
                  </span>
                </td>
                <td className="px-4 py-3 text-xs">
                  {item.location_name || 'Not assigned'}
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="flex justify-end space-x-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-7 w-7 p-0 text-neutral-400 hover:text-white"
                      aria-label={`View ${item.name}`}
                    >
                      <Eye className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-7 w-7 p-0 text-neutral-400 hover:text-white"
                      aria-label={`Edit ${item.name}`}
                    >
                      <Pen className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-7 w-7 p-0 text-neutral-400 hover:text-red-400"
                      aria-label={`Delete ${item.name}`}
                    >
                      <Trash className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default InventoryTable;
