import Box from '@mui/material/Box'
import Typography from '@mui/material/Typography'

export function Footer() {
  return (
    <Box
      component="footer"
      sx={{
        borderTop: 1,
        borderColor: 'divider',
        py: 2,
        textAlign: 'center',
      }}
    >
      <Typography variant="body2" color="text.secondary">
        © {new Date().getFullYear()} Calendar — Gestión de horarios
      </Typography>
    </Box>
  )
}
