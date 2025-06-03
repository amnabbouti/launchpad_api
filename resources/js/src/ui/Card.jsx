import * as React from 'react';

function Card({ className = '', ...props }) {
  const classes =
    `bg-white text-gray-900 dark:bg-gray-800 dark:text-white flex flex-col gap-6 rounded-xl border border-gray-200 dark:border-gray-700 py-6 shadow-sm ${className}`.trim();
  return <div data-slot="card" className={classes} {...props} />;
}

function CardHeader({ className = '', ...props }) {
  const classes =
    `@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6 ${className}`.trim();
  return <div data-slot="card-header" className={classes} {...props} />;
}

function CardTitle({ className = '', ...props }) {
  const classes = `leading-none font-semibold ${className}`.trim();
  return <div data-slot="card-title" className={classes} {...props} />;
}

function CardDescription({ className = '', ...props }) {
  const classes =
    `text-gray-500 dark:text-gray-400 text-sm ${className}`.trim();
  return <div data-slot="card-description" className={classes} {...props} />;
}

function CardAction({ className = '', ...props }) {
  const classes =
    `col-start-2 row-span-2 row-start-1 self-start justify-self-end ${className}`.trim();
  return <div data-slot="card-action" className={classes} {...props} />;
}

function CardContent({ className = '', ...props }) {
  const classes = `px-6 ${className}`.trim();
  return <div data-slot="card-content" className={classes} {...props} />;
}

function CardFooter({ className = '', ...props }) {
  const classes = `flex items-center px-6 [.border-t]:pt-6 ${className}`.trim();
  return <div data-slot="card-footer" className={classes} {...props} />;
}

export {
  Card,
  CardHeader,
  CardFooter,
  CardTitle,
  CardAction,
  CardDescription,
  CardContent,
};
