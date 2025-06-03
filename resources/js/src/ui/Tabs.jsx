import * as React from 'react';

function Tabs({
  className = '',
  children,
  defaultValue,
  value,
  onValueChange,
}) {
  const [activeTab, setActiveTab] = React.useState(value || defaultValue || '');

  const handleChange = (newValue) => {
    setActiveTab(newValue);
    if (onValueChange) onValueChange(newValue);
  };

  const classes = `flex flex-col gap-2 ${className}`.trim();

  return (
    <div className={classes}>
      {React.Children.map(children, (child) =>
        React.isValidElement(child)
          ? React.cloneElement(child, { activeTab, setActiveTab: handleChange })
          : child,
      )}
    </div>
  );
}

function TabsList({ className = '', children }) {
  const classes =
    `bg-black/80 text-blue-400/70 inline-flex h-9 w-fit items-center justify-center rounded-lg p-[3px] border border-blue-500/30 ${className}`.trim();

  return <div className={classes}>{children}</div>;
}

function TabsTrigger({
  className = '',
  value,
  activeTab,
  setActiveTab,
  children,
}) {
  const isActive = activeTab === value;

  const classes = `
    ${
      isActive
        ? 'bg-blue-500/10 text-blue-300 border-b-2 border-blue-500'
        : 'text-blue-400/70 hover:text-blue-300 hover:bg-blue-500/5'
    }
    inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-2 rounded-t-md px-4 py-2 text-sm font-mono transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:pointer-events-none disabled:opacity-50 ${className}
  `.trim();

  return (
    <button className={classes} onClick={() => setActiveTab(value)}>
      {children}
    </button>
  );
}

export { Tabs, TabsList, TabsTrigger };
