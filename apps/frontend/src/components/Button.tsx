import './Button.css';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger';
  fullWidth?: boolean;
}

export default function Button({ variant = 'primary', fullWidth = false, children, ...props }: ButtonProps) {
  return (
    <button className={[`btn`, `btn-${variant}`, fullWidth ? 'btn-full' : ''].join(' ')} {...props}>
      {children}
    </button>
  );
}
