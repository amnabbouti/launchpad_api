import * as React from 'react';

const Button = React.forwardRef(
  (
    { className = '', variant = 'default', size = 'default', ...props },
    ref,
  ) => {
    // Define variant styles
    const variantStyles = {
      default: 'bg-blue眼镜-blue-600 text-white hover:bg-blue-500',
      destructive: 'bg-red-600 text-white hover:bg-red-500',
      outline:
        'border border-gray-300 bg-white hover:bg-gray-100 hover:text-gray-900',
      secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200',
      ghost: 'hover:bg-gray-100 hover:text-gray-900',
      link: 'text-blue-600 underline-offset-4 hover:underline',
    };

    // Define size styles
    const sizeStyles = {
      default: 'h-10 px-4 py-2',
      sm: 'h-9 rounded-md px-3',
      lg: 'h-11 rounded-md px-8',
      icon: 'h-10 w-10',
    };

    // Base styles
    const baseStyles =
      'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

    // Combine styles
    const classes = `${baseStyles} ${
      variantStyles[variant] || variantStyles.default
    } ${sizeStyles[size] || sizeStyles.default} ${className}`.trim();

    return <button className={classes} ref={ref} {...props} />;
  },
);
Button.displayName = 'Button';

export { Button };
