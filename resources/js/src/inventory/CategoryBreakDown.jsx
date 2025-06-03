import * as React from "react";

function CategoryBreakdown({ categories, total }) {
  return (
    <div className="bg-neutral-800 rounded-lg p-3 dark:bg-neutral-800">
      <h4 className="text-sm font-mono mb-3 text-white dark:text-white">CATEGORY BREAKDOWN</h4>
      <div className="space-y-3">
        {categories.map((category, idx) => (
          <div key={idx}>
            <div className="flex justify-between text-xs mb-1 text-white dark:text-white">
              <span>{category.name}</span>
              <span className="font-mono">
                {category.count} items ({Math.round((category.count / total) * 100)}%)
              </span>
            </div>
            <div className="h-2 bg-neutral-700 rounded-full dark:bg-neutral-700">
              <div
                className={`h-full rounded-full ${
                  idx % 2 === 0 ? "bg-blue-500 dark:bg-blue-500" : "bg-purple-500 dark:bg-purple-500"
                }`}
                style={{
                  width: `${(category.count / total) * 100}%`,
                }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default CategoryBreakdown;