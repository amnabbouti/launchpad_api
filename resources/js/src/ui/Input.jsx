import * as React from "react";

function Input({ className = "", ...props }) {
  const baseStyles =
    "w-full px-3 py-2 bg-neutral-800 border border-neutral-700 rounded-md text-neutral-100 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500";
  const classes = `${baseStyles} ${className}`.trim();

  return <input className={classes} {...props} />;
}

export { Input };