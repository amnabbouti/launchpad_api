import * as React from 'react';

function Sheet({ children, isOpen, onClose, ...props }) {
  React.useEffect(() => {
    // Handle Escape key to close
    const handleEscape = (e) => {
      if (e.key === 'Escape' && isOpen && onClose) {
        onClose();
      }
    };
    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div className="sheet-container" {...props}>
      {children}
    </div>
  );
}

function SheetTrigger({ children, onClick, ...props }) {
  return (
    <div className="sheet-trigger" onClick={onClick} {...props}>
      {children}
    </div>
  );
}

function SheetClose({ onClick, ...props }) {
  return (
    <button
      className="sheet-close"
      onClick={onClick}
      aria-label="Close"
      {...props}
    >
      <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLineJoin="round"
      >
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>
  );
}

function SheetContent({ className = '', children, side = 'right', ...props }) {
  // Define side-specific styles
  const sideStyles = {
    right: {
      transform: 'translateX(100%)',
      animationOpen: 'slideInRight 0.5s ease-out forwards',
      animationClose: 'slideOutRight 0.3s ease-in-out forwards',
      top: '0',
      right: '0',
      height: '100%',
      width: '75vw',
      maxWidth: '24rem',
      borderLeft: '1px solid #ccc',
    },
    left: {
      transform: 'translateX(-100%)',
      top: '0',
      height: '100%',
      width: '75vw',
      maxWidth: '24rem',
      borderRight: '1px solid #ccc',
    },
    top: {
      transform: 'translateY(-100%)',
      left: '0',
      width: '100%',
      height: 'auto',
      borderBottom: '1px solid #ccc',
      top: '-0',
    },
    bottom: {
      transform: 'translateY(100%)',
      left: '0',
      width: '100%',
      height: 'auto',
      borderTop: '1px solid #ccc',
      top: 'auto',
      bottom: '-0',
    },
  };

  const styles = sideStyles[side] || sideStyles.right;

  return (
    <div
      className="sheet-content"
      style={{
        position: 'fixed',
        zIndex: 50,
        display: 'flex',
        flexDirection: 'column',
        gap: '1rem',
        background: '#fff',
        color: '#000',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
        animation: isOpen ? styles.animationOpen : styles.animationClose,
        transform: styles.transform,
        top: styles.top,
        right: styles.right,
        left: styles.left,
        bottom: styles.bottom,
        height: styles.height,
        width: styles.width,
        maxWidth: styles.maxWidth,
        borderLeft: styles.borderLeft,
        borderRight: styles.borderRight,
        borderTop: styles.borderTop,
        borderBottom: styles.borderBottom,
        ...props.style,
      }}
      {...props}
    >
      {children}
      <SheetClose
        onClick={onClose}
        style={{
          position: 'absolute',
          top: '1rem',
          right: '1rem',
          background: 'transparent',
          border: 'none',
          opacity: 0.7,
          cursor: 'pointer',
          padding: '0.25rem',
        }}
      />
    </div>
  );
}

function SheetHeader({ className = '', ...props }) {
  const classes = `flex flex-col gap-1.5 p-4 ${className}`.trim();
  return <div className={classes} {...props} />;
}

function SheetFooter({ className = '', ...props }) {
  const classes =
    `margin-top: auto; flex flex-col gap-2 p-4 ${className}`.trim();
  return <div className={classes} {...props} />;
}

function SheetTitle({ className = '', ...props }) {
  const classes = `color: #000; font-weight: 600; ${className}`.trim();
  return <h2 className={classes} {...props} />;
}

function SheetDescription({ className = '', ...props }) {
  const classes = `color: #6b7280; font-size: 0.875rem; ${className}`.trim();
  return <p className={classes} {...props} />;
}

export {
  Sheet,
  SheetTrigger,
  SheetClose,
  SheetContent,
  SheetHeader,
  SheetFooter,
  SheetTitle,
  SheetDescription,
};
