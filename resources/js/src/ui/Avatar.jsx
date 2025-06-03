import * as React from "react";

function Avatar({ className = "", children, ...props }) {
  const classes = `position: relative; display: flex; width: 2rem; height: 2rem; flex-shrink: 0; overflow: hidden; border-radius: 9999px; ${className}`.trim();
  return (
    <div className={classes} {...props}>
      {children}
    </div>
  );
}

function AvatarImage({ className = "", src, alt = "", ...props }) {
  const [hasError, setHasError] = React.useState(false);

  if (!src || hasError) return null;

  const classes = `aspect-ratio: 1 / 1; width: 100%; height: 100%; object-fit: cover; ${className}`.trim();
  return (
    <img
      className={classes}
      src={src}
      alt={alt}
      onError={() => setHasError(true)}
      {...props}
    />
  );
}

function AvatarFallback({ className = "", children, ...props }) {
  const classes = `background-color: #e5e7eb; display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; ${className}`.trim();
  return (
    <div className={classes} {...props}>
      {children}
    </div>
  );
}

export { Avatar, AvatarImage, AvatarFallback };